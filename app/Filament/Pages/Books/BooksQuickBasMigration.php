<?php

namespace App\Filament\Pages\Books;

use App\Models\BookDocument;
use App\Models\BookImport;
use App\Models\BookImportRow;
use App\Services\Books\BankCsvImportService;
use App\Services\Books\QuickBasImportService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
use RuntimeException;

class BooksQuickBasMigration extends BookPage
{
    use WithFileUploads;

    protected string $view = 'filament.pages.books.quickbas-migration';
    protected static ?string $slug = 'books/quickbas-migration';

    public $incomeFile = null;
    public $expenseFile = null;
    public array $basFiles = [];

    public array $analysisPreview = [];
    public ?int $removeMigrationId = null;

    public function mount(): void
    {
        $this->mountBookContext();
        abort_unless(auth()->user()?->can_manage_users, 403);
    }

    public static function canAccess(): bool
    {
        return parent::canAccess() && (auth()->user()?->can_manage_users ?? false);
    }

    protected function bookBusinessChanged(): void
    {
        $this->resetWorkspace();
    }

    public function updatedIncomeFile(): void
    {
        $this->analysisPreview = [];
    }

    public function updatedExpenseFile(): void
    {
        $this->analysisPreview = [];
    }

    public function updatedBasFiles(): void
    {
        $this->analysisPreview = [];
    }

    public function getBooksStorageReadyProperty(): bool
    {
        $disk = config('filesystems.disks.books', []);

        return class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class)
            && filled($disk['key'] ?? null)
            && filled($disk['secret'] ?? null)
            && filled($disk['bucket'] ?? null)
            && filled($disk['endpoint'] ?? null);
    }

    public function getHistoricalMigrationsProperty()
    {
        return BookImport::query()
            ->withCount(['rows'])
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->where('type', 'quickbas')
            ->where('status', 'complete')
            ->orderByDesc('period_start')
            ->get();
    }

    public function getRemovingMigrationProperty(): ?BookImport
    {
        if (! $this->removeMigrationId) {
            return null;
        }

        return BookImport::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->where('type', 'quickbas')
            ->where('status', 'complete')
            ->find($this->removeMigrationId);
    }

    public function analyseFiles(): void
    {
        $this->validateSourceFiles();

        try {
            $service = app(QuickBasImportService::class);
            $analysis = $service->analyse(
                $this->incomeFile->getRealPath(),
                $this->expenseFile->getRealPath(),
            );

            $this->analysisPreview = $service->preview($analysis, $this->getBookBusiness());
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'quickbas' => $exception->getMessage(),
            ]);
        }
    }

    public function importHistory(): void
    {
        $this->validateSourceFiles();

        try {
            $quickBas = app(QuickBasImportService::class);
            $analysis = $quickBas->analyse(
                $this->incomeFile->getRealPath(),
                $this->expenseFile->getRealPath(),
            );

            if (! collect($analysis['checks'])->every(fn ($check) => $check === true)) {
                throw ValidationException::withMessages([
                    'quickbas' => 'The calculated totals do not match the TOTAL rows in the QuickBAS exports. Do not import this year until the source files are checked.',
                ]);
            }

            $result = $quickBas->migrate(
                $this->getBookBusiness(),
                $analysis,
                $this->incomeFile->getClientOriginalName(),
                $this->expenseFile->getClientOriginalName(),
            );

            [$storedDocuments, $storageFailures] = $this->archiveSourceDocuments(
                $result['import'],
                $result['financial_year']->id,
            );

            $suggestionsUpdated = $this->refreshPendingBankSuggestions();
            $fyLabel = $analysis['financial_year']['label'];
            $transactionCount = count($analysis['rows']);

            $body = $transactionCount . ' transactions imported for ' . $fyLabel
                . '. ' . $result['created_categories'] . ' categories created and '
                . $result['matched_categories'] . ' matched.';

            if ($suggestionsUpdated > 0) {
                $body .= ' ' . $suggestionsUpdated . ' staged bank rows now have category suggestions from this history.';
            }

            if ($this->booksStorageReady) {
                $body .= ' ' . $storedDocuments . ' source ' . Str::plural('document', $storedDocuments) . ' archived to Spaces.';

                if ($storageFailures > 0) {
                    $body .= ' ' . $storageFailures . ' document upload(s) could not be archived.';
                }
            } else {
                $body .= ' Spaces is not configured yet, so the source files were not archived.';
            }

            Notification::make()
                ->success()
                ->title('QuickBAS history imported')
                ->body($body)
                ->persistent()
                ->send();

            $this->resetWorkspace();
            $this->dispatch('book-transaction-updated');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'quickbas' => $exception->getMessage(),
            ]);
        }
    }

    public function askToRemoveMigration(int $importId): void
    {
        $import = BookImport::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->where('type', 'quickbas')
            ->where('status', 'complete')
            ->findOrFail($importId);

        $this->removeMigrationId = $import->id;
        $this->dispatch('open-modal', id: 'bookQuickBasRemoveConfirm');
    }

    public function removeMigration(): void
    {
        if (! $this->removeMigrationId) {
            return;
        }

        $import = BookImport::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->where('type', 'quickbas')
            ->where('status', 'complete')
            ->findOrFail($this->removeMigrationId);

        $documentPaths = BookDocument::query()
            ->where('book_import_id', $import->id)
            ->get(['id', 'disk', 'path']);

        DB::transaction(function () use ($import): void {
            BookDocument::query()
                ->where('book_import_id', $import->id)
                ->delete();

            $import->business->basPeriods()
                ->where('period_type', 'annual')
                ->whereDate('start_date', $import->period_start)
                ->whereDate('end_date', $import->period_end)
                ->where('status', 'historical')
                ->delete();

            $import->business->transactions()
                ->where('book_import_id', $import->id)
                ->delete();

            $import->delete();
        });

        foreach ($documentPaths as $document) {
            if ($document->path && Storage::disk($document->disk ?: 'books')->exists($document->path)) {
                Storage::disk($document->disk ?: 'books')->delete($document->path);
            }
        }

        $this->removeMigrationId = null;
        $this->dispatch('close-modal', id: 'bookQuickBasRemoveConfirm');
        $this->dispatch('book-transaction-updated');

        Notification::make()
            ->success()
            ->title('QuickBAS migration removed')
            ->body('The migrated transactions, migration rows and historical annual BAS summary were removed. Categories were kept.')
            ->send();
    }

    protected function validateSourceFiles(): void
    {
        $this->validate([
            'incomeFile' => ['required', 'file', 'max:10240'],
            'expenseFile' => ['required', 'file', 'max:10240'],
            'basFiles.*' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);
    }

    protected function archiveSourceDocuments(BookImport $import, int $financialYearId): array
    {
        if (! $this->booksStorageReady) {
            return [0, 0];
        }

        $directory = 'books/business-' . $this->getBookBusiness()->id
            . '/' . $import->period_start->format('Y') . '-' . $import->period_end->format('Y')
            . '/quickbas/import-' . $import->id;

        $files = [
            [
                'file' => $this->incomeFile,
                'document_type' => 'quickbas_income_export',
                'period' => 'annual',
            ],
            [
                'file' => $this->expenseFile,
                'document_type' => 'quickbas_expense_export',
                'period' => 'annual',
            ],
        ];

        foreach ($this->basFiles as $basFile) {
            $files[] = [
                'file' => $basFile,
                'document_type' => 'quickbas_bas_pdf',
                'period' => $this->guessBasPeriod($basFile->getClientOriginalName()),
            ];
        }

        $stored = 0;
        $failed = 0;
        $usedNames = [];

        foreach ($files as $index => $item) {
            $file = $item['file'];

            if (! $file) {
                continue;
            }

            $originalName = $file->getClientOriginalName();
            $safeName = $this->safeFilename($originalName);

            if (isset($usedNames[$safeName])) {
                $safeName = ($index + 1) . '-' . $safeName;
            }
            $usedNames[$safeName] = true;

            try {
                $path = Storage::disk('books')->putFileAs($directory, $file, $safeName);

                if (! $path) {
                    $failed++;
                    continue;
                }

                BookDocument::create([
                    'book_business_id' => $this->getBookBusiness()->id,
                    'book_financial_year_id' => $financialYearId,
                    'book_import_id' => $import->id,
                    'document_type' => $item['document_type'],
                    'period' => $item['period'],
                    'original_filename' => $originalName,
                    'disk' => 'books',
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_at' => now(),
                ]);

                $stored++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        return [$stored, $failed];
    }

    protected function refreshPendingBankSuggestions(): int
    {
        $business = $this->getBookBusiness();
        $csv = app(BankCsvImportService::class);
        $rules = $csv->rulesFor($business);
        $history = $csv->historicalHintsFor($business);
        $updated = 0;

        BookImportRow::query()
            ->where('status', 'pending')
            ->whereNull('book_category_id')
            ->whereHas('import', function (Builder $query) use ($business): void {
                $query
                    ->where('book_business_id', $business->id)
                    ->where('type', 'bank')
                    ->where('status', 'review');
            })
            ->whereNotNull('amount')
            ->whereNotNull('description')
            ->chunkById(200, function ($rows) use ($csv, $rules, $history, &$updated): void {
                foreach ($rows as $row) {
                    $suggestion = $csv->suggestCategory(
                        (float) $row->amount,
                        (string) $row->description,
                        $rules,
                        $history,
                    );

                    if (! $suggestion) {
                        continue;
                    }

                    $raw = $row->raw_data ?? [];
                    $raw['suggestion_source'] = $suggestion['source'] ?? null;
                    $raw['history_count'] = $suggestion['history_count'] ?? null;

                    $row->update([
                        'suggested_book_category_id' => $suggestion['category_id'],
                        'suggestion_confidence' => $suggestion['confidence'],
                        'raw_data' => $raw,
                    ]);
                    $updated++;
                }
            });

        return $updated;
    }

    protected function guessBasPeriod(string $filename): string
    {
        $name = strtolower($filename);

        return match (true) {
            str_contains($name, 'annual'), str_contains($name, 'year') => 'annual',
            preg_match('/(?:^|[^a-z])q1(?:[^a-z]|$)/', $name) === 1, str_contains($name, 'jul-sep'), str_contains($name, 'jul_sep') => 'q1',
            preg_match('/(?:^|[^a-z])q2(?:[^a-z]|$)/', $name) === 1, str_contains($name, 'oct-dec'), str_contains($name, 'oct_dec') => 'q2',
            preg_match('/(?:^|[^a-z])q3(?:[^a-z]|$)/', $name) === 1, str_contains($name, 'jan-mar'), str_contains($name, 'jan_mar') => 'q3',
            preg_match('/(?:^|[^a-z])q4(?:[^a-z]|$)/', $name) === 1, str_contains($name, 'apr-jun'), str_contains($name, 'apr_jun') => 'q4',
            default => 'unknown',
        };
    }

    protected function safeFilename(string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $base = Str::slug($base) ?: 'quickbas-file';

        return $extension !== '' ? $base . '.' . strtolower($extension) : $base;
    }

    protected function resetWorkspace(): void
    {
        $this->incomeFile = null;
        $this->expenseFile = null;
        $this->basFiles = [];
        $this->analysisPreview = [];
        $this->resetValidation();
    }
}
