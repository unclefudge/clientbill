<?php

namespace App\Filament\Pages\Books;

use App\Filament\Resources\BookCategories\BookCategoryResource;
use App\Models\BookBankAccount;
use App\Models\BookCategory;
use App\Models\BookBasPeriod;
use App\Models\BookCategoryRule;
use App\Models\BookImport as BookImportModel;
use App\Models\BookImportRow;
use App\Models\BookFinancialYear;
use App\Models\BookTransaction;
use App\Services\Books\BankCsvImportService;
use App\Services\Books\BookTransactionAmountService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class BooksImport extends BookPage
{
    use WithFileUploads;
    use WithPagination;

    protected string $view = 'filament.pages.books.import';
    protected static ?string $slug = 'books/import';

    public int $importCount = 0;
    public int $pendingCount = 0;

    public ?int $currentImportId = null;
    public ?int $selectedBankAccountId = null;
    public $csvFile = null;

    public bool $hasHeader = false;
    public int $dateColumn = 0;
    public int $amountColumn = 1;
    public int $descriptionColumn = 2;
    public $balanceColumn = 3;
    public string $dateFormat = 'd/m/Y';

    public array $rawPreviewRows = [];
    public array $mappedPreviewRows = [];

    public string $reviewFilter = 'all';

    public string $newBankName = '';
    public string $newBankInstitution = 'Commonwealth Bank';
    public string $newBankLastFour = '';

    public ?int $editingImportRowId = null;
    public ?int $rowCategoryId = null;
    public float $rowBusinessUsePercentage = 100;
    public string $rowGstTreatment = 'gst_applicable';
    public bool $rowIncludesGst = true;
    public ?string $rowGstAmountInput = null;
    public bool $rowGstAmountOverridden = false;
    public string $rowPurchaseType = 'non_capital';
    public bool $rowRememberMerchant = false;

    public ?int $discardImportId = null;

    public function mount(): void
    {
        $this->mountBookContext();
        $this->selectDefaultBankAccount();
        $this->loadData();
    }

    protected function bookBusinessChanged(): void
    {
        $this->resetImportWorkspace();
        $this->selectDefaultBankAccount();
        $this->loadData();
    }

    public function updatedSelectedBankAccountId(): void
    {
        $this->applySavedFormat();
        $this->refreshPreview();
    }

    public function updatedCsvFile(): void
    {
        $this->validate([
            'csvFile' => ['required', 'file', 'max:10240'],
        ]);

        $this->currentImportId = null;
        $this->applySavedFormatOrGuess();
        $this->refreshPreview();
    }

    public function updatedHasHeader(): void
    {
        $this->refreshPreview();
    }

    public function updatedDateColumn(): void
    {
        $this->refreshPreview();
    }

    public function updatedAmountColumn(): void
    {
        $this->refreshPreview();
    }

    public function updatedDescriptionColumn(): void
    {
        $this->refreshPreview();
    }

    public function updatedBalanceColumn(): void
    {
        $this->refreshPreview();
    }

    public function updatedDateFormat(): void
    {
        $this->refreshPreview();
    }

    public function updatedReviewFilter(): void
    {
        $this->resetPage();
    }

    public function getBankAccountsProperty()
    {
        return BookBankAccount::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }

    public function getCategoriesProperty()
    {
        return BookCategory::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }

    public function getGstRegisteredProperty(): bool
    {
        return (bool) $this->getBookBusiness()->gst_registered;
    }

    public function getDateFormatsProperty(): array
    {
        return BankCsvImportService::DATE_FORMATS;
    }

    public function getColumnOptionsProperty(): array
    {
        $columnCount = 0;

        foreach ($this->rawPreviewRows as $row) {
            $columnCount = max($columnCount, count($row));
        }

        $options = [];
        $sample = $this->rawPreviewRows[$this->hasHeader ? 1 : 0] ?? $this->rawPreviewRows[0] ?? [];

        for ($index = 0; $index < $columnCount; $index++) {
            $value = trim((string) ($sample[$index] ?? ''));
            $value = mb_strlen($value) > 30 ? mb_substr($value, 0, 27) . '…' : $value;
            $options[$index] = 'Column ' . ($index + 1) . ($value !== '' ? ' — ' . $value : '');
        }

        return $options;
    }

    public function getCurrentImportProperty(): ?BookImportModel
    {
        if (! $this->currentImportId) {
            return null;
        }

        return BookImportModel::query()
            ->with('bankAccount')
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->find($this->currentImportId);
    }

    public function getReviewRowsProperty()
    {
        if (! $this->currentImportId) {
            return null;
        }

        return BookImportRow::query()
            ->with(['suggestedCategory', 'category'])
            ->where('book_import_id', $this->currentImportId)
            ->when($this->reviewFilter === 'needs_review', fn (Builder $query) => $query->whereIn('status', ['pending', 'invalid']))
            ->when($this->reviewFilter === 'ready', fn (Builder $query) => $query->whereIn('status', ['ready', 'included']))
            ->when($this->reviewFilter === 'personal', fn (Builder $query) => $query->where('status', 'excluded_personal'))
            ->when($this->reviewFilter === 'duplicates', fn (Builder $query) => $query->where('status', 'duplicate'))
            ->orderBy('transaction_date')
            ->orderBy('row_number')
            ->paginate(50);
    }

    public function getReviewStatsProperty(): array
    {
        if (! $this->currentImportId) {
            return $this->emptyStats();
        }

        $counts = BookImportRow::query()
            ->where('book_import_id', $this->currentImportId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'total' => (int) $counts->sum(),
            'pending' => (int) ($counts['pending'] ?? 0),
            'ready' => (int) ($counts['ready'] ?? 0),
            'personal' => (int) ($counts['excluded_personal'] ?? 0),
            'duplicates' => (int) ($counts['duplicate'] ?? 0),
            'invalid' => (int) ($counts['invalid'] ?? 0),
            'included' => (int) ($counts['included'] ?? 0),
        ];
    }

    public function getCanFinalizeProperty(): bool
    {
        $import = $this->currentImport;
        $stats = $this->reviewStats;

        return $import
            && $import->status === 'review'
            && $stats['pending'] === 0
            && $stats['invalid'] === 0;
    }

    public function getDiscardingImportProperty(): ?BookImportModel
    {
        if (! $this->discardImportId) {
            return null;
        }

        return BookImportModel::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->where('status', 'review')
            ->find($this->discardImportId);
    }

    public function getEditingRowProperty(): ?BookImportRow
    {
        if (! $this->editingImportRowId) {
            return null;
        }

        return BookImportRow::query()
            ->whereKey($this->editingImportRowId)
            ->whereHas('import', fn (Builder $query) => $query->where('book_business_id', $this->getBookBusiness()->id))
            ->first();
    }

    public function getRecentImportsProperty()
    {
        return BookImportModel::query()
            ->with('bankAccount')
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->latest('id')
            ->limit(10)
            ->get();
    }

    public function startImport(): void
    {
        $csvService = app(BankCsvImportService::class);
        $this->validate([
            'selectedBankAccountId' => ['required', 'integer'],
            'csvFile' => ['required', 'file', 'max:10240'],
            'dateColumn' => ['required', 'integer', 'min:0'],
            'amountColumn' => ['required', 'integer', 'min:0'],
            'descriptionColumn' => ['required', 'integer', 'min:0'],
            'dateFormat' => ['required', 'in:' . implode(',', array_keys(BankCsvImportService::DATE_FORMATS))],
        ]);

        $business = $this->getBookBusiness();
        $bankAccount = BookBankAccount::query()
            ->where('book_business_id', $business->id)
            ->whereKey($this->selectedBankAccountId)
            ->firstOrFail();

        $this->validateMapping();

        $format = $this->mappingArray();
        $parsedRows = iterator_to_array($csvService->parse($this->csvFile->getRealPath(), $format), false);

        if ($parsedRows === []) {
            throw ValidationException::withMessages(['csvFile' => 'No transaction rows were found in this file.']);
        }

        $rules = $csvService->rulesFor($business);
        $historicalHints = $csvService->historicalHintsFor($business);

        $prepared = [];
        $fingerprints = [];

        foreach ($parsedRows as $row) {
            $fingerprint = null;

            if ($row['transaction_date'] && $row['amount'] !== null) {
                $fingerprint = $csvService->fingerprint(
                    $business->id,
                    $bankAccount->id,
                    $row['transaction_date'],
                    (float) $row['amount'],
                    $row['description'],
                    $row['balance'] === null ? null : (float) $row['balance'],
                );
                $fingerprints[] = $fingerprint;
            }

            $row['fingerprint'] = $fingerprint;
            $prepared[] = $row;
        }

        $existingFingerprints = BookImportRow::query()
            ->whereNotNull('fingerprint')
            ->whereIn('fingerprint', array_values(array_unique(array_filter($fingerprints))))
            ->whereHas('import', function (Builder $query) use ($business, $bankAccount): void {
                $query
                    ->where('book_business_id', $business->id)
                    ->where('book_bank_account_id', $bankAccount->id);
            })
            ->pluck('fingerprint')
            ->flip();

        $import = DB::transaction(function () use (
            $prepared,
            $format,
            $business,
            $bankAccount,
            $existingFingerprints,
            $rules,
            $historicalHints,
            $csvService,
        ) {
            $validDates = collect($prepared)->pluck('transaction_date')->filter()->sort()->values();

            $import = BookImportModel::create([
                'book_business_id' => $business->id,
                'book_bank_account_id' => $bankAccount->id,
                'type' => 'bank',
                'status' => 'review',
                'original_filename' => $this->csvFile->getClientOriginalName(),
                'format_snapshot' => $format,
                'period_start' => $validDates->first(),
                'period_end' => $validDates->last(),
                'row_count' => count($prepared),
            ]);

            $seenInThisFile = [];
            $duplicateCount = 0;

            foreach ($prepared as $row) {
                $status = 'pending';
                $suggestion = null;

                if ($row['errors'] !== []) {
                    $status = 'invalid';
                } elseif ($row['fingerprint'] && (
                    $existingFingerprints->has($row['fingerprint'])
                    || isset($seenInThisFile[$row['fingerprint']])
                )) {
                    $status = 'duplicate';
                    $duplicateCount++;
                } else {
                    $suggestion = $csvService->suggestCategory(
                        (float) $row['amount'],
                        $row['description'],
                        $rules,
                        $historicalHints,
                    );
                }

                if ($row['fingerprint']) {
                    $seenInThisFile[$row['fingerprint']] = true;
                }

                $importRow = BookImportRow::create([
                    'book_import_id' => $import->id,
                    'suggested_book_category_id' => $suggestion['category_id'] ?? null,
                    'row_number' => $row['row_number'],
                    'transaction_date' => $row['transaction_date'],
                    'amount' => $row['amount'],
                    'balance' => $row['balance'],
                    'description' => $row['description'],
                    'fingerprint' => $row['fingerprint'],
                    'status' => $status,
                    'suggestion_confidence' => $suggestion['confidence'] ?? null,
                    'raw_data' => [
                        'columns' => $row['raw_data'],
                        'errors' => $row['errors'],
                        'suggestion_source' => $suggestion['source'] ?? null,
                        'suggestion_rule_id' => $suggestion['rule_id'] ?? null,
                    ],
                ]);

                // Explicit Category Rules are trusted. Historical learning remains a suggestion.
                if ($status === 'pending' && ($suggestion['source'] ?? null) === 'rule') {
                    $matchedRule = $rules->firstWhere('id', (int) ($suggestion['rule_id'] ?? 0));

                    if ($matchedRule) {
                        $this->autoConfirmRowFromRule($importRow, $matchedRule, (int) ($suggestion['confidence'] ?? 100));
                    }
                }
            }

            // Derive International Transaction Fee suggestions after all normal rows
            // have been created and trusted category rules have auto-confirmed.
            $this->refreshLinkedInternationalFeeSuggestions($import->id);

            $import->update(['duplicate_count' => $duplicateCount]);
            $bankAccount->update(['import_format' => $format]);

            return $import;
        });

        $this->currentImportId = $import->id;
        $this->csvFile = null;
        $this->rawPreviewRows = [];
        $this->mappedPreviewRows = [];
        $this->reviewFilter = 'all';
        $this->resetPage();
        $this->loadData();

        Notification::make()
            ->success()
            ->title('CSV ready to review')
            ->body($import->row_count . ' bank rows loaded. Confirm the business transactions before importing them.')
            ->send();
    }

    public function selectCategory(int $rowId, $categoryId): void
    {
        $row = $this->findAccessibleRow($rowId);
        $categoryId = filled($categoryId) ? (int) $categoryId : null;

        if (! $categoryId) {
            $row->update([
                'book_category_id' => null,
                'status' => 'pending',
                'confirmed_at' => null,
                'gst_treatment' => null,
                'gst_amount' => null,
                'business_use_percentage' => null,
                'purchase_type' => null,
            ]);
            $this->loadData();

            return;
        }

        $wasConfirmed = $row->status === 'ready' && $row->confirmed_at !== null;
        $category = $this->findValidCategoryForRow($row, $categoryId);
        $isInternationalFee = app(BankCsvImportService::class)
            ->isInternationalTransactionFee((string) $row->description);

        $businessUse = (float) $category->default_business_use_percentage;
        $gstTreatment = $isInternationalFee ? 'gst_free' : $category->default_gst_treatment;
        $gstAmount = $this->calculateRowGstAmount(
            (float) $row->amount,
            $businessUse,
            $gstTreatment,
        );

        $rawData = $row->raw_data ?? [];
        $rawData['gst_amount_overridden'] = false;

        $row->update([
            'book_category_id' => $category->id,
            'status' => 'ready',
            'confirmed_at' => now(),
            'gst_treatment' => $gstTreatment,
            'gst_amount' => $gstAmount,
            'business_use_percentage' => $businessUse,
            'purchase_type' => (float) $row->amount < 0
                ? ($isInternationalFee ? 'non_capital' : $category->default_purchase_type)
                : null,
            'raw_data' => $rawData,
        ]);

        if (! $wasConfirmed) {
            $this->incrementConfirmedSuggestionRule($row, $category->id);
        }

        $this->teachCurrentImportFromRow($row->fresh(), $category);
        $this->refreshLinkedInternationalFeeSuggestions();
        $this->loadData();
    }

    public function useSuggestion(int $rowId): void
    {
        $row = $this->findAccessibleRow($rowId);

        if (! $row->suggested_book_category_id) {
            return;
        }

        $this->selectCategory($rowId, $row->suggested_book_category_id);
    }

    public function editImportRow(int $rowId): void
    {
        $row = $this->findAccessibleRow($rowId);

        abort_unless(in_array($row->status, ['pending', 'ready'], true), 422);

        $this->editingImportRowId = $row->id;
        $this->rowCategoryId = $row->book_category_id;

        if (! $this->rowCategoryId && $row->suggested_book_category_id) {
            $this->rowCategoryId = $row->suggested_book_category_id;
        }

        $category = $this->rowCategoryId
            ? $this->findValidCategoryForRow($row, $this->rowCategoryId)
            : null;

        $this->rowBusinessUsePercentage = $row->business_use_percentage !== null
            ? (float) $row->business_use_percentage
            : (float) ($category?->default_business_use_percentage ?? 100);
        $isInternationalFee = app(BankCsvImportService::class)
            ->isInternationalTransactionFee((string) $row->description);

        $this->rowGstTreatment = $row->gst_treatment
            ?: ($isInternationalFee
                ? 'gst_free'
                : ($category?->default_gst_treatment ?? ($this->gstRegistered ? 'gst_applicable' : 'gst_free')));
        $this->rowIncludesGst = $this->rowGstTreatment === 'gst_applicable' && $this->gstRegistered;
        $this->rowGstAmountOverridden = (bool) (($row->raw_data ?? [])['gst_amount_overridden'] ?? false);
        $gstAmount = $row->gst_amount !== null
            ? (float) $row->gst_amount
            : $this->calculateRowGstAmount((float) $row->amount, $this->rowBusinessUsePercentage, $this->rowGstTreatment);
        $this->rowGstAmountInput = number_format($gstAmount, 2, '.', '');
        $this->rowPurchaseType = $row->purchase_type
            ?: ($isInternationalFee ? 'non_capital' : ($category?->default_purchase_type ?? 'non_capital'));
        $this->rowRememberMerchant = false;

        $this->dispatch('open-modal', id: 'bookImportRowEditor');
    }

    public function updatedRowCategoryId($categoryId): void
    {
        if (! $this->editingImportRowId || ! $categoryId) {
            return;
        }

        $row = $this->findAccessibleRow($this->editingImportRowId);
        $category = $this->findValidCategoryForRow($row, (int) $categoryId);

        $isInternationalFee = app(BankCsvImportService::class)
            ->isInternationalTransactionFee((string) $row->description);

        $this->rowBusinessUsePercentage = (float) $category->default_business_use_percentage;
        $this->rowGstTreatment = $isInternationalFee ? 'gst_free' : $category->default_gst_treatment;
        $this->rowIncludesGst = $this->rowGstTreatment === 'gst_applicable' && $this->gstRegistered;
        $this->rowGstAmountOverridden = false;
        $this->rowPurchaseType = $isInternationalFee ? 'non_capital' : $category->default_purchase_type;
        $this->refreshRowAutoGstAmount();
    }

    public function updatedRowBusinessUsePercentage(): void
    {
        if (! $this->rowGstAmountOverridden) {
            $this->refreshRowAutoGstAmount();
        }
    }

    public function updatedRowIncludesGst(bool $includesGst): void
    {
        if ($this->rowGstTreatment === 'excluded') {
            $this->rowIncludesGst = false;
            $this->rowGstAmountInput = '0.00';

            return;
        }

        $this->rowGstTreatment = ($includesGst && $this->gstRegistered) ? 'gst_applicable' : 'gst_free';
        $this->rowGstAmountOverridden = false;
        $this->refreshRowAutoGstAmount();
    }

    public function updatedRowGstAmountInput(): void
    {
        if ($this->gstRegistered && $this->rowIncludesGst && $this->rowGstTreatment !== 'excluded') {
            $this->rowGstAmountOverridden = true;
        }
    }

    public function recalculateRowGstAmount(): void
    {
        $this->rowGstAmountOverridden = false;
        $this->refreshRowAutoGstAmount();
    }

    public function saveImportRowAdjustments(): void
    {
        if (! $this->editingImportRowId) {
            return;
        }

        $validated = $this->validate([
            'rowCategoryId' => ['required', 'integer'],
            'rowBusinessUsePercentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'rowIncludesGst' => ['required', 'boolean'],
            'rowGstAmountInput' => ['nullable', 'numeric', 'min:0'],
            'rowPurchaseType' => ['required', 'in:non_capital,capital'],
            'rowRememberMerchant' => ['required', 'boolean'],
        ]);

        $row = $this->findAccessibleRow($this->editingImportRowId);
        $wasConfirmed = $row->status === 'ready' && $row->confirmed_at !== null;
        $category = $this->findValidCategoryForRow($row, (int) $validated['rowCategoryId']);
        $businessUse = (float) $validated['rowBusinessUsePercentage'];

        $gstTreatment = $this->rowGstTreatment === 'excluded'
            ? 'excluded'
            : (($this->gstRegistered && $validated['rowIncludesGst']) ? 'gst_applicable' : 'gst_free');

        $gstAmount = 0.0;

        if ($gstTreatment === 'gst_applicable') {
            $gstAmount = $validated['rowGstAmountInput'] !== null
                ? round((float) $validated['rowGstAmountInput'], 2)
                : $this->calculateRowGstAmount((float) $row->amount, $businessUse, $gstTreatment);

            $businessAmount = abs((float) $row->amount * ($businessUse / 100));

            if ($gstAmount > $businessAmount + 0.005) {
                throw ValidationException::withMessages([
                    'rowGstAmountInput' => 'GST cannot be greater than the business-use amount.',
                ]);
            }
        }

        $rawData = $row->raw_data ?? [];
        $rawData['gst_amount_overridden'] = $this->rowGstAmountOverridden;

        $row->update([
            'book_category_id' => $category->id,
            'status' => 'ready',
            'confirmed_at' => now(),
            'gst_treatment' => $gstTreatment,
            'gst_amount' => $gstAmount,
            'business_use_percentage' => $businessUse,
            'purchase_type' => (float) $row->amount < 0 ? $validated['rowPurchaseType'] : null,
            'raw_data' => $rawData,
        ]);

        if (! $wasConfirmed) {
            $this->incrementConfirmedSuggestionRule($row, $category->id);
        }

        $rememberedRule = null;

        if ($validated['rowRememberMerchant']) {
            $rememberedRule = $this->rememberMerchantRule($row, $category);
        }

        if ($rememberedRule) {
            $this->autoConfirmPendingRowsForRule($rememberedRule, $row->id);
        }

        $this->teachCurrentImportFromRow($row->fresh(), $category);
        $this->refreshLinkedInternationalFeeSuggestions();

        $this->dispatch('close-modal', id: 'bookImportRowEditor');
        $this->editingImportRowId = null;
        $this->rowRememberMerchant = false;
        $this->loadData();

        Notification::make()->success()->title('Transaction review updated')->send();
    }

    protected function teachCurrentImportFromRow(BookImportRow $confirmedRow, BookCategory $category): void
    {
        if (! $this->currentImportId || $confirmedRow->book_import_id !== $this->currentImportId) {
            return;
        }

        $service = app(BankCsvImportService::class);

        if ($service->isInternationalTransactionFee((string) $confirmedRow->description)) {
            return;
        }

        $merchantKey = $service->merchantKey((string) $confirmedRow->description);

        if (mb_strlen($merchantKey) < 4) {
            return;
        }

        $direction = (float) $confirmedRow->amount >= 0 ? 'income' : 'expense';

        $pendingRows = BookImportRow::query()
            ->where('book_import_id', $this->currentImportId)
            ->where('status', 'pending')
            ->where('id', '!=', $confirmedRow->id)
            ->get();

        foreach ($pendingRows as $pendingRow) {
            $pendingDirection = (float) $pendingRow->amount >= 0 ? 'income' : 'expense';

            if ($pendingDirection !== $direction) {
                continue;
            }

            if ($service->isInternationalTransactionFee((string) $pendingRow->description)) {
                continue;
            }

            if ($service->merchantKey((string) $pendingRow->description) !== $merchantKey) {
                continue;
            }

            $rawData = $pendingRow->raw_data ?? [];
            $rawData['suggestion_source'] = 'same_import';
            $rawData['learned_from_row_id'] = $confirmedRow->id;
            unset($rawData['suggestion_rule_id']);

            $pendingRow->update([
                'suggested_book_category_id' => $category->id,
                'suggestion_confidence' => 95,
                'raw_data' => $rawData,
            ]);
        }
    }

    protected function rememberMerchantRule(BookImportRow $row, BookCategory $category): ?BookCategoryRule
    {
        $service = app(BankCsvImportService::class);

        if ($service->isInternationalTransactionFee((string) $row->description)) {
            return null;
        }

        $matchValue = $service->merchantKey((string) $row->description);

        if (mb_strlen($matchValue) < 4) {
            return null;
        }

        $rule = BookCategoryRule::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->where('match_type', 'starts_with')
            ->whereRaw('LOWER(match_value) = ?', [mb_strtolower($matchValue)])
            ->first();

        if (! $rule) {
            return BookCategoryRule::create([
                'book_business_id' => $this->getBookBusiness()->id,
                'book_category_id' => $category->id,
                'match_type' => 'starts_with',
                'match_value' => $matchValue,
                'times_confirmed' => 1,
                'active' => true,
            ])->load('category');
        }

        $rule->update([
            'book_category_id' => $category->id,
            'active' => true,
            'times_confirmed' => $rule->times_confirmed + 1,
        ]);

        return $rule->fresh('category');
    }

    protected function refreshLinkedInternationalFeeSuggestions(?int $importId = null): void
    {
        $importId ??= $this->currentImportId;

        if (! $importId) {
            return;
        }

        $service = app(BankCsvImportService::class);

        $rows = BookImportRow::query()
            ->with(['category', 'suggestedCategory'])
            ->where('book_import_id', $importId)
            ->orderBy('row_number')
            ->get();

        $feeRows = $rows->filter(fn (BookImportRow $row): bool =>
            $row->status === 'pending'
            && $row->amount !== null
            && $row->transaction_date !== null
            && $service->isInternationalTransactionFee((string) $row->description)
        );

        foreach ($feeRows as $feeRow) {
            $candidates = $rows
                ->filter(function (BookImportRow $candidate) use ($feeRow, $service): bool {
                    if ($candidate->id === $feeRow->id) {
                        return false;
                    }

                    if (! in_array($candidate->status, ['pending', 'ready', 'included'], true)) {
                        return false;
                    }

                    if ($candidate->amount === null || (float) $candidate->amount >= 0) {
                        return false;
                    }

                    if ($candidate->transaction_date?->toDateString() !== $feeRow->transaction_date?->toDateString()) {
                        return false;
                    }

                    if ($service->isInternationalTransactionFee((string) $candidate->description)) {
                        return false;
                    }

                    return (bool) ($candidate->book_category_id ?: $candidate->suggested_book_category_id);
                })
                ->values();

            $linked = $this->chooseLinkedInternationalFeeParent($feeRow, $candidates);

            if (! $linked) {
                $rawData = $feeRow->raw_data ?? [];

                if (($rawData['suggestion_source'] ?? null) === 'linked_international_fee') {
                    unset(
                        $rawData['suggestion_source'],
                        $rawData['linked_parent_row_id'],
                        $rawData['linked_parent_description'],
                        $rawData['linked_method'],
                    );

                    $feeRow->update([
                        'suggested_book_category_id' => null,
                        'suggestion_confidence' => null,
                        'gst_treatment' => null,
                        'gst_amount' => null,
                        'purchase_type' => null,
                        'raw_data' => $rawData,
                    ]);
                }

                continue;
            }

            /** @var BookImportRow $parent */
            $parent = $linked['row'];
            $categoryId = (int) ($parent->book_category_id ?: $parent->suggested_book_category_id);

            $rawData = $feeRow->raw_data ?? [];
            $rawData['suggestion_source'] = 'linked_international_fee';
            $rawData['linked_parent_row_id'] = $parent->id;
            $rawData['linked_parent_description'] = (string) $parent->description;
            $rawData['linked_method'] = $linked['method'];
            unset($rawData['suggestion_rule_id'], $rawData['learned_from_row_id']);

            $feeRow->update([
                'suggested_book_category_id' => $categoryId,
                'suggestion_confidence' => $linked['confidence'],
                'gst_treatment' => 'gst_free',
                'gst_amount' => 0,
                'purchase_type' => 'non_capital',
                'raw_data' => $rawData,
            ]);
        }
    }

    protected function chooseLinkedInternationalFeeParent(BookImportRow $feeRow, $candidates): ?array
    {
        if ($candidates->isEmpty()) {
            return null;
        }

        $ranked = $candidates
            ->map(function (BookImportRow $candidate) use ($feeRow): array {
                $distance = abs((int) $candidate->row_number - (int) $feeRow->row_number);
                $isPrevious = (int) $candidate->row_number < (int) $feeRow->row_number;
                $rawData = $candidate->raw_data ?? [];

                $isTrustedRule = (bool) ($rawData['auto_confirmed_by_rule'] ?? false)
                    || (($rawData['suggestion_source'] ?? null) === 'rule' && $candidate->book_category_id !== null);

                return [
                    'row' => $candidate,
                    'distance' => $distance,
                    'side_rank' => $isPrevious ? 0 : 1,
                    'is_trusted_rule' => $isTrustedRule,
                    'is_confirmed' => $candidate->book_category_id !== null,
                ];
            })
            ->sortBy([
                ['distance', 'asc'],
                ['side_rank', 'asc'],
            ])
            ->values();

        $best = $ranked->first();

        // In CBA exports the fee generally sits immediately beside the purchase.
        if ($best['distance'] === 1) {
            $confidence = $best['is_trusted_rule']
                ? 97
                : ($best['is_confirmed']
                    ? 95
                    : min(92, max(85, (int) ($best['row']->suggestion_confidence ?? 85))));

            return [
                'row' => $best['row'],
                'confidence' => $confidence,
                'method' => 'adjacent_same_date',
            ];
        }

        // Conservative fallback: if only one categorised/suggested expense exists
        // on that date, use it. Otherwise leave the fee for manual Review.
        if ($ranked->count() === 1) {
            $confidence = $best['is_trusted_rule']
                ? 92
                : ($best['is_confirmed']
                    ? 90
                    : min(88, max(82, (int) ($best['row']->suggestion_confidence ?? 82))));

            return [
                'row' => $best['row'],
                'confidence' => $confidence,
                'method' => 'only_categorised_purchase_same_date',
            ];
        }

        return null;
    }

    protected function autoConfirmPendingRowsFromSavedRules(): void
    {
        if (! $this->currentImportId) {
            return;
        }

        $service = app(BankCsvImportService::class);
        $rules = $service->rulesFor($this->getBookBusiness());

        if ($rules->isEmpty()) {
            return;
        }

        $rows = BookImportRow::query()
            ->where('book_import_id', $this->currentImportId)
            ->where('status', 'pending')
            ->get();

        foreach ($rows as $row) {
            if (($row->raw_data ?? [])['skip_auto_rule'] ?? false) {
                continue;
            }

            if ($row->amount === null) {
                continue;
            }

            $suggestion = $service->suggestCategory(
                (float) $row->amount,
                (string) $row->description,
                $rules,
                [],
            );

            if (($suggestion['source'] ?? null) !== 'rule') {
                continue;
            }

            $rule = $rules->firstWhere('id', (int) ($suggestion['rule_id'] ?? 0));

            if ($rule) {
                $this->autoConfirmRowFromRule($row, $rule, (int) ($suggestion['confidence'] ?? 100));
            }
        }
    }

    protected function autoConfirmPendingRowsForRule(BookCategoryRule $rule, ?int $exceptRowId = null): void
    {
        if (! $this->currentImportId) {
            return;
        }

        $service = app(BankCsvImportService::class);
        $rule->loadMissing('category');
        $rules = collect([$rule]);

        $rows = BookImportRow::query()
            ->where('book_import_id', $this->currentImportId)
            ->where('status', 'pending')
            ->when($exceptRowId, fn (Builder $query) => $query->whereKeyNot($exceptRowId))
            ->get();

        foreach ($rows as $row) {
            if (($row->raw_data ?? [])['skip_auto_rule'] ?? false) {
                continue;
            }

            if ($row->amount === null) {
                continue;
            }

            $suggestion = $service->suggestCategory(
                (float) $row->amount,
                (string) $row->description,
                $rules,
                [],
            );

            if (($suggestion['source'] ?? null) === 'rule') {
                $this->autoConfirmRowFromRule($row, $rule, (int) ($suggestion['confidence'] ?? 100));
            }
        }
    }

    protected function autoConfirmRowFromRule(BookImportRow $row, BookCategoryRule $rule, int $confidence = 100): void
    {
        $rule->loadMissing('category');
        $category = $rule->category;

        if (! $category || ! $category->active) {
            return;
        }

        $direction = (float) $row->amount >= 0 ? 'income' : 'expense';

        if (! in_array($category->type, [$direction, 'other'], true)) {
            return;
        }

        $businessUse = $rule->business_use_percentage !== null
            ? (float) $rule->business_use_percentage
            : (float) $category->default_business_use_percentage;

        $gstTreatment = filled($rule->gst_treatment)
            ? (string) $rule->gst_treatment
            : (string) $category->default_gst_treatment;

        $gstAmount = $this->calculateRowGstAmount(
            (float) $row->amount,
            $businessUse,
            $gstTreatment,
        );

        $rawData = $row->raw_data ?? [];
        $rawData['suggestion_source'] = 'rule';
        $rawData['suggestion_rule_id'] = $rule->id;
        $rawData['auto_confirmed_by_rule'] = true;
        $rawData['gst_amount_overridden'] = false;
        unset($rawData['skip_auto_rule']);

        $row->update([
            'suggested_book_category_id' => $category->id,
            'suggestion_confidence' => $confidence,
            'book_category_id' => $category->id,
            'status' => 'ready',
            'confirmed_at' => now(),
            'gst_treatment' => $gstTreatment,
            'gst_amount' => $gstAmount,
            'business_use_percentage' => $businessUse,
            'purchase_type' => (float) $row->amount < 0 ? $category->default_purchase_type : null,
            'raw_data' => $rawData,
        ]);

        // "Confirmed" now also counts trusted rule auto-confirmations.
        $rule->increment('times_confirmed');
    }

    protected function incrementConfirmedSuggestionRule(BookImportRow $row, int $categoryId): void
    {
        if ((int) $row->suggested_book_category_id !== $categoryId) {
            return;
        }

        $ruleId = (int) (($row->raw_data ?? [])['suggestion_rule_id'] ?? 0);

        if (! $ruleId) {
            return;
        }

        BookCategoryRule::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->whereKey($ruleId)
            ->increment('times_confirmed');
    }

    public function markPersonal(int $rowId): void
    {
        $row = $this->findAccessibleRow($rowId);

        abort_unless(in_array($row->status, ['pending', 'ready', 'excluded_personal'], true), 422);

        $row->update([
            'book_category_id' => null,
            'status' => 'excluded_personal',
            'confirmed_at' => now(),
            'gst_treatment' => null,
            'gst_amount' => null,
            'business_use_percentage' => null,
            'purchase_type' => null,
        ]);

        $this->refreshLinkedInternationalFeeSuggestions();
        $this->loadData();
    }

    public function resetDecision(int $rowId): void
    {
        $row = $this->findAccessibleRow($rowId);

        abort_if($row->status === 'included', 422);

        $rawData = $row->raw_data ?? [];

        // If a trusted rule auto-confirmed this row, Reset means "do not auto-apply
        // that rule again to this staged row". The saved rule itself is unchanged.
        if (($rawData['auto_confirmed_by_rule'] ?? false) === true) {
            $rawData['skip_auto_rule'] = true;
        }

        $row->update([
            'book_category_id' => null,
            'status' => 'pending',
            'confirmed_at' => null,
            'gst_treatment' => null,
            'gst_amount' => null,
            'business_use_percentage' => null,
            'purchase_type' => null,
            'raw_data' => $rawData,
        ]);

        $this->refreshLinkedInternationalFeeSuggestions();
        $this->loadData();
    }

    public function treatDuplicateAsNew(int $rowId): void
    {
        $row = $this->findAccessibleRow($rowId);
        abort_unless($row->status === 'duplicate', 422);

        $row->update([
            'status' => 'pending',
            'confirmed_at' => null,
        ]);

        $this->refreshLinkedInternationalFeeSuggestions();
        $this->loadData();
    }

    public function finalizeImport(): void
    {
        $amountService = app(BookTransactionAmountService::class);
        $import = $this->currentImport;
        abort_unless($import && $import->status === 'review', 404);

        $stats = $this->reviewStats;

        if ($stats['pending'] > 0 || $stats['invalid'] > 0) {
            Notification::make()
                ->warning()
                ->title('Review is not finished')
                ->body('Resolve all uncategorised or invalid rows before completing this import.')
                ->send();

            return;
        }

        $business = $this->getBookBusiness();
        $bankAccount = $import->bankAccount;

        $closedFinancialYears = BookFinancialYear::query()
            ->where('book_business_id', $business->id)
            ->where('status', 'closed')
            ->get();

        $lockedRow = BookImportRow::query()
            ->where('book_import_id', $import->id)
            ->where('status', 'ready')
            ->whereNotNull('transaction_date')
            ->get(['transaction_date'])
            ->first(function (BookImportRow $row) use ($closedFinancialYears): bool {
                $date = $row->transaction_date->toDateString();

                return $closedFinancialYears->contains(fn (BookFinancialYear $financialYear) =>
                    $date >= $financialYear->start_date->toDateString()
                    && $date <= $financialYear->end_date->toDateString()
                );
            });

        if ($lockedRow) {
            $date = $lockedRow->transaction_date->toDateString();
            $financialYear = $closedFinancialYears->first(fn (BookFinancialYear $financialYear) =>
                $date >= $financialYear->start_date->toDateString()
                && $date <= $financialYear->end_date->toDateString()
            );

            Notification::make()
                ->warning()
                ->title(($financialYear?->label ?? 'Financial year') . ' is closed')
                ->body('This import contains a business transaction in a closed financial year. Reopen that year before completing the import.')
                ->send();

            return;
        }

        $lodgedBasPeriods = BookBasPeriod::query()
            ->where('book_business_id', $business->id)
            ->where('period_type', 'quarterly')
            ->where('status', 'lodged')
            ->get();

        $lodgedBasRow = BookImportRow::query()
            ->where('book_import_id', $import->id)
            ->where('status', 'ready')
            ->whereNotNull('transaction_date')
            ->get(['transaction_date'])
            ->first(function (BookImportRow $row) use ($lodgedBasPeriods): bool {
                $date = $row->transaction_date->toDateString();

                return $lodgedBasPeriods->contains(fn (BookBasPeriod $period) =>
                    $date >= $period->start_date->toDateString()
                    && $date <= $period->end_date->toDateString()
                );
            });

        if ($lodgedBasRow) {
            $date = $lodgedBasRow->transaction_date->toDateString();
            $basPeriod = $lodgedBasPeriods->first(fn (BookBasPeriod $period) =>
                $date >= $period->start_date->toDateString()
                && $date <= $period->end_date->toDateString()
            );

            Notification::make()
                ->warning()
                ->title(($basPeriod?->period_label ?? 'BAS period') . ' is lodged')
                ->body('This import contains a business transaction in a lodged BAS period. Reopen that BAS period before completing the import.')
                ->send();

            return;
        }

        DB::transaction(function () use ($import, $business, $bankAccount, $amountService): void {
            $rows = BookImportRow::query()
                ->with('category')
                ->where('book_import_id', $import->id)
                ->where('status', 'ready')
                ->lockForUpdate()
                ->get();

            foreach ($rows as $row) {
                $category = $row->category;

                if (! $category || $row->amount === null || ! $row->transaction_date) {
                    throw ValidationException::withMessages([
                        'import' => 'A transaction is missing its category, date or amount.',
                    ]);
                }

                $businessUse = $row->business_use_percentage !== null
                    ? (float) $row->business_use_percentage
                    : (float) $category->default_business_use_percentage;
                $gstTreatment = $row->gst_treatment ?: $category->default_gst_treatment;
                $amounts = $amountService->calculate(
                    $business,
                    (float) $row->amount,
                    $businessUse,
                    $gstTreatment,
                );

                if ($gstTreatment === 'gst_applicable' && $business->gst_registered && $row->gst_amount !== null) {
                    $businessAmount = round((float) $row->amount * ($businessUse / 100), 4);
                    $gstAmount = round((float) $row->gst_amount, 2);
                    $netAbsolute = round(max(0, abs($businessAmount) - $gstAmount), 2);

                    $amounts = [
                        'business_amount' => $businessAmount,
                        'net_amount' => $businessAmount < 0 ? -$netAbsolute : $netAbsolute,
                        'gst_amount' => $gstAmount,
                    ];
                }

                $transaction = BookTransaction::create([
                    'book_business_id' => $business->id,
                    'book_bank_account_id' => $bankAccount?->id,
                    'book_category_id' => $category->id,
                    'book_import_id' => $import->id,
                    'transaction_date' => $row->transaction_date,
                    'amount' => $row->amount,
                    'business_use_percentage' => $businessUse,
                    'business_amount' => $amounts['business_amount'],
                    'net_amount' => $amounts['net_amount'],
                    'gst_amount' => $amounts['gst_amount'],
                    'gst_treatment' => $gstTreatment,
                    'sale_type' => (float) $row->amount >= 0
                        ? match ($gstTreatment) {
                            'gst_applicable' => 'gst',
                            'gst_free' => 'gst_free',
                            default => 'excluded',
                        }
                        : null,
                    'purchase_type' => (float) $row->amount < 0
                        ? ($row->purchase_type ?: $category->default_purchase_type)
                        : null,
                    'source' => 'bank_import',
                    'payment_source' => 'Business bank account',
                    'description' => $row->description,
                ]);

                $row->update([
                    'book_transaction_id' => $transaction->id,
                    'status' => 'included',
                    'confirmed_at' => $row->confirmed_at ?? now(),
                ]);
            }

            $counts = BookImportRow::query()
                ->where('book_import_id', $import->id)
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            $import->update([
                'status' => 'complete',
                'included_count' => (int) ($counts['included'] ?? 0),
                'excluded_count' => (int) ($counts['excluded_personal'] ?? 0),
                'duplicate_count' => (int) ($counts['duplicate'] ?? 0),
                'imported_at' => now(),
            ]);
        });

        $this->loadData();
        $this->dispatch('book-transaction-updated');

        Notification::make()
            ->success()
            ->title('Bank import completed')
            ->body($import->fresh()->included_count . ' business transactions were added to Books.')
            ->send();
    }

    public function openImport(int $importId): void
    {
        $import = BookImportModel::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->findOrFail($importId);

        $this->currentImportId = $import->id;
        $this->selectedBankAccountId = $import->book_bank_account_id;

        if ($import->status === 'review') {
            $this->autoConfirmPendingRowsFromSavedRules();
            $this->refreshLinkedInternationalFeeSuggestions();
        }

        $this->reviewFilter = 'all';
        $this->resetPage();
        $this->loadData();
    }

    public function newImport(): void
    {
        $this->currentImportId = null;
        $this->csvFile = null;
        $this->rawPreviewRows = [];
        $this->mappedPreviewRows = [];
        $this->reviewFilter = 'all';
        $this->selectDefaultBankAccount();
        $this->resetPage();
    }

    public function askToDiscardImport(int $importId): void
    {
        $import = BookImportModel::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->where('status', 'review')
            ->findOrFail($importId);

        abort_if($import->rows()->whereNotNull('book_transaction_id')->exists(), 422);

        $this->discardImportId = $import->id;
        $this->dispatch('open-modal', id: 'bookDiscardImportConfirm');
    }

    public function discardImport(): void
    {
        if (! $this->discardImportId) {
            return;
        }

        $importId = $this->discardImportId;
        $import = BookImportModel::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->where('status', 'review')
            ->findOrFail($importId);

        abort_if($import->rows()->whereNotNull('book_transaction_id')->exists(), 422);

        $import->delete();
        $this->discardImportId = null;
        $this->dispatch('close-modal', id: 'bookDiscardImportConfirm');

        if ($this->currentImportId === $importId) {
            $this->newImport();
        }

        $this->loadData();

        Notification::make()
            ->success()
            ->title('Import discarded')
            ->body('The staged bank rows were removed. No Books transactions were affected.')
            ->send();
    }

    public function openBankAccountModal(): void
    {
        $this->newBankName = '';
        $this->newBankInstitution = 'Commonwealth Bank';
        $this->newBankLastFour = '';
        $this->dispatch('open-modal', id: 'bookBankAccountQuickCreate');
    }

    public function createBankAccount(): void
    {
        $validated = $this->validate([
            'newBankName' => ['required', 'string', 'max:255'],
            'newBankInstitution' => ['nullable', 'string', 'max:255'],
            'newBankLastFour' => ['nullable', 'digits:4'],
        ]);

        $account = BookBankAccount::create([
            'book_business_id' => $this->getBookBusiness()->id,
            'name' => $validated['newBankName'],
            'institution' => $validated['newBankInstitution'] ?: null,
            'last_four' => $validated['newBankLastFour'] ?: null,
            'active' => true,
        ]);

        $this->selectedBankAccountId = $account->id;
        $this->dispatch('close-modal', id: 'bookBankAccountQuickCreate');

        Notification::make()->success()->title('Bank account added')->send();
    }

    public function getCategoriesUrlProperty(): string
    {
        return BookCategoryResource::getUrl();
    }

    protected function loadData(): void
    {
        $business = $this->getBookBusiness();

        $this->importCount = BookImportModel::where('book_business_id', $business->id)->count();
        $this->pendingCount = BookImportRow::whereHas(
            'import',
            fn ($query) => $query->where('book_business_id', $business->id)
        )->whereIn('status', ['pending', 'invalid'])->count();
    }

    protected function selectDefaultBankAccount(): void
    {
        $account = BookBankAccount::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->where('active', true)
            ->orderBy('id')
            ->first();

        $this->selectedBankAccountId = $account?->id;
        $this->applySavedFormat();
    }

    protected function applySavedFormat(): void
    {
        if (! $this->selectedBankAccountId) {
            return;
        }

        $account = BookBankAccount::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->find($this->selectedBankAccountId);

        if ($account?->import_format) {
            $this->setFormat($account->import_format);
        }
    }

    protected function applySavedFormatOrGuess(): void
    {
        if (! $this->csvFile) {
            return;
        }

        $account = $this->selectedBankAccountId
            ? BookBankAccount::query()
                ->where('book_business_id', $this->getBookBusiness()->id)
                ->find($this->selectedBankAccountId)
            : null;

        $format = $account?->import_format;

        if (! $format) {
            $format = app(BankCsvImportService::class)->guessFormat($this->csvFile->getRealPath());
        }

        $this->setFormat($format);
    }

    protected function setFormat(array $format): void
    {
        $this->hasHeader = (bool) ($format['has_header'] ?? false);
        $this->dateColumn = (int) ($format['date_column'] ?? 0);
        $this->amountColumn = (int) ($format['amount_column'] ?? 1);
        $this->descriptionColumn = (int) ($format['description_column'] ?? 2);
        $this->balanceColumn = array_key_exists('balance_column', $format) ? $format['balance_column'] : 3;
        $this->dateFormat = (string) ($format['date_format'] ?? 'd/m/Y');
    }

    protected function refreshPreview(): void
    {
        if (! $this->csvFile) {
            return;
        }

        $service = app(BankCsvImportService::class);
        $path = $this->csvFile->getRealPath();

        $this->rawPreviewRows = $service->rawPreview($path, 6);
        $this->mappedPreviewRows = $service->preview($path, $this->mappingArray(), 6);
    }

    protected function mappingArray(): array
    {
        return [
            'has_header' => $this->hasHeader,
            'date_column' => $this->dateColumn,
            'amount_column' => $this->amountColumn,
            'description_column' => $this->descriptionColumn,
            'balance_column' => $this->balanceColumn === '' || $this->balanceColumn === null ? null : (int) $this->balanceColumn,
            'date_format' => $this->dateFormat,
        ];
    }

    protected function validateMapping(): void
    {
        $required = [$this->dateColumn, $this->amountColumn, $this->descriptionColumn];

        if (count(array_unique($required)) !== count($required)) {
            throw ValidationException::withMessages([
                'mapping' => 'Date, amount and description must use different columns.',
            ]);
        }

        if ($this->balanceColumn !== null && $this->balanceColumn !== '' && in_array((int) $this->balanceColumn, $required, true)) {
            throw ValidationException::withMessages([
                'mapping' => 'Balance must use a different column, or be set to Not included.',
            ]);
        }
    }

    public function gstPreviewForRow(BookImportRow $row): array
    {
        $category = $row->category ?: $row->suggestedCategory;

        if (! $category) {
            return ['known' => false, 'estimated' => false, 'amount' => 0.0];
        }

        $businessUse = $row->business_use_percentage !== null
            ? (float) $row->business_use_percentage
            : (float) $category->default_business_use_percentage;
        $gstTreatment = $row->gst_treatment ?: $category->default_gst_treatment;

        if (! $this->gstRegistered || $gstTreatment !== 'gst_applicable') {
            return ['known' => true, 'estimated' => $row->book_category_id === null, 'amount' => 0.0];
        }

        $amount = $row->gst_amount !== null
            ? (float) $row->gst_amount
            : $this->calculateRowGstAmount((float) $row->amount, $businessUse, $gstTreatment);

        return [
            'known' => true,
            'estimated' => $row->book_category_id === null,
            'amount' => $amount,
        ];
    }

    protected function refreshRowAutoGstAmount(): void
    {
        if (! $this->editingImportRowId) {
            return;
        }

        $row = $this->findAccessibleRow($this->editingImportRowId);
        $gstTreatment = $this->rowGstTreatment === 'excluded'
            ? 'excluded'
            : (($this->gstRegistered && $this->rowIncludesGst) ? 'gst_applicable' : 'gst_free');

        $this->rowGstAmountInput = number_format(
            $this->calculateRowGstAmount((float) $row->amount, $this->rowBusinessUsePercentage, $gstTreatment),
            2,
            '.',
            ''
        );
    }

    protected function calculateRowGstAmount(float $signedAmount, float $businessUsePercentage, string $gstTreatment): float
    {
        $business = $this->getBookBusiness();

        if (! $business->gst_registered || $gstTreatment !== 'gst_applicable' || (float) $business->gst_rate <= 0) {
            return 0.0;
        }

        $businessGross = abs($signedAmount) * ($businessUsePercentage / 100);
        $gstRate = (float) $business->gst_rate;

        return round($businessGross * ($gstRate / (100 + $gstRate)), 2);
    }

    protected function findAccessibleRow(int $rowId): BookImportRow
    {
        return BookImportRow::query()
            ->whereKey($rowId)
            ->whereHas('import', fn (Builder $query) => $query->where('book_business_id', $this->getBookBusiness()->id))
            ->firstOrFail();
    }

    protected function findValidCategoryForRow(BookImportRow $row, int $categoryId): BookCategory
    {
        $direction = (float) $row->amount >= 0 ? 'income' : 'expense';

        return BookCategory::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->whereKey($categoryId)
            ->where('active', true)
            ->whereIn('type', [$direction, 'other'])
            ->firstOrFail();
    }

    protected function resetImportWorkspace(): void
    {
        $this->currentImportId = null;
        $this->selectedBankAccountId = null;
        $this->csvFile = null;
        $this->rawPreviewRows = [];
        $this->mappedPreviewRows = [];
        $this->reviewFilter = 'all';
        $this->editingImportRowId = null;
        $this->rowCategoryId = null;
        $this->rowBusinessUsePercentage = 100;
        $this->rowGstTreatment = $this->gstRegistered ? 'gst_applicable' : 'gst_free';
        $this->rowIncludesGst = $this->gstRegistered;
        $this->rowGstAmountInput = null;
        $this->rowGstAmountOverridden = false;
        $this->rowPurchaseType = 'non_capital';
        $this->resetPage();
    }

    protected function emptyStats(): array
    {
        return [
            'total' => 0,
            'pending' => 0,
            'ready' => 0,
            'personal' => 0,
            'duplicates' => 0,
            'invalid' => 0,
            'included' => 0,
        ];
    }
}
