<?php

namespace App\Filament\Pages\Books;

use App\Models\BookBasPeriod;
use App\Models\BookCategory;
use App\Models\BookFinancialYear;
use App\Models\BookDocument;
use App\Models\BookTransaction;
use App\Services\Books\BookDocumentStorage;
use Filament\Notifications\Notification;
use Livewire\WithFileUploads;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BooksTransactions extends BookPage
{
    use WithFileUploads;

    protected string $view = 'filament.pages.books.transactions';
    protected static ?string $slug = 'books/transactions';

    public string $search = '';
    public string $directionFilter = 'all';
    public ?int $categoryFilter = null;
    public string $financialYearFilter = 'all';
    public string $periodFilter = 'all';

    public ?int $editingTransactionId = null;
    public string $transactionType = 'expense';
    public ?string $transactionDate = null;
    public ?string $amountInput = null;
    public ?int $editingCategoryId = null;
    public string $description = '';
    public string $paymentSource = 'Business bank account';
    public float $businessUsePercentage = 100;
    public string $gstTreatment = 'gst_applicable';
    public bool $includesGst = true;
    public bool $excludeFromBas = false;
    public ?string $gstAmountInput = null;
    public bool $gstAmountOverridden = false;
    public string $purchaseType = 'non_capital';
    public string $notes = '';
    public string $transactionSource = 'manual';

    public ?int $documentTransactionId = null;
    public ?int $documentPendingDeleteId = null;
    public array $documentUploads = [];
    public string $documentNotes = '';

    public function mount(): void
    {
        $this->mountBookContext();
        $this->transactionDate = now('Australia/Hobart')->toDateString();
        $this->setDefaultFinancialYearFilter();
    }

    protected function bookBusinessChanged(): void
    {
        $this->categoryFilter = null;
        $this->periodFilter = 'all';
        $this->setDefaultFinancialYearFilter();
    }

    public function updatedFinancialYearFilter(): void
    {
        // Quarter buttons are relative to one Australian financial year.
        if ($this->financialYearFilter === 'all') {
            $this->periodFilter = 'all';
        }
    }

    public function getCategoriesProperty()
    {
        return BookCategory::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->orderBy('name')
            ->get();
    }

    public function getActiveCategoriesProperty()
    {
        return $this->categories->where('active', true)->values();
    }

    public function getEditorCategoriesProperty()
    {
        return $this->categories
            ->filter(fn (BookCategory $category) => $category->active || $category->id === $this->editingCategoryId)
            ->values();
    }

    public function getFinancialYearsProperty()
    {
        return BookFinancialYear::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->orderByDesc('start_date')
            ->get();
    }

    public function getLodgedBasPeriodsProperty()
    {
        return BookBasPeriod::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->where('period_type', 'quarterly')
            ->where('status', 'lodged')
            ->orderBy('start_date')
            ->get();
    }

    public function getFinancialYearOptionsProperty(): array
    {
        return ['all' => 'All financial years'] + $this->financialYears
            ->mapWithKeys(fn (BookFinancialYear $financialYear) => [
                (string) $financialYear->id => $financialYear->label,
            ])
            ->all();
    }

    public function getGstRegisteredProperty(): bool
    {
        return (bool) $this->getBookBusiness()->gst_registered;
    }

    public function getFilteredPeriodLabelProperty(): string
    {
        if ($this->financialYearFilter === 'all') {
            return 'All financial years';
        }

        $financialYear = $this->financialYears->firstWhere('id', (int) $this->financialYearFilter);

        if (! $financialYear) {
            return 'Selected transactions';
        }

        if (! in_array($this->periodFilter, ['q1', 'q2', 'q3', 'q4'], true)) {
            return $financialYear->label . ' · Full financial year';
        }

        $quarterIndex = match ($this->periodFilter) {
            'q1' => 0,
            'q2' => 1,
            'q3' => 2,
            'q4' => 3,
        };

        $start = $financialYear->start_date->copy()->addMonths($quarterIndex * 3);
        $end = $start->copy()->addMonths(3)->subDay();

        if ($end->gt($financialYear->end_date)) {
            $end = $financialYear->end_date->copy();
        }

        return strtoupper($this->periodFilter)
            . ' · '
            . $start->format('j M Y')
            . ' – '
            . $end->format('j M Y');
    }

    public function getFilterDescriptionProperty(): string
    {
        $parts = [];

        $parts[] = match ($this->directionFilter) {
            'income' => 'Income only',
            'expense' => 'Expenses only',
            default => 'Income & expenses',
        };

        if ($this->categoryFilter) {
            $category = $this->categories->firstWhere('id', $this->categoryFilter);

            if ($category) {
                $parts[] = $category->name . ($category->active ? '' : ' (inactive)');
            }
        } else {
            $parts[] = 'All categories';
        }

        if (trim($this->search) !== '') {
            $parts[] = 'Search: “' . trim($this->search) . '”';
        }

        return implode(' · ', $parts);
    }

    public function exportCsv(): StreamedResponse
    {
        $business = $this->getBookBusiness();
        $transactions = $this->transactions;
        $summary = $this->transactionSummary;

        $filename = $this->safeFilename(
            ($business->legal_name ?: $business->name)
            . ' - Transaction Report - '
            . $this->filteredPeriodLabel
            . '.csv'
        );

        return response()->streamDownload(
            function () use ($business, $transactions, $summary): void {
                $out = fopen('php://output', 'w');

                // Excel-friendly UTF-8 BOM.
                fwrite($out, "\xEF\xBB\xBF");

                fputcsv($out, ['Business', $business->legal_name ?: $business->name]);

                if ($business->abn) {
                    fputcsv($out, ['ABN', $business->abn]);
                }

                fputcsv($out, ['Report', 'Transaction Report']);
                fputcsv($out, ['Period', $this->filteredPeriodLabel]);
                fputcsv($out, ['Filters', $this->filterDescription]);
                fputcsv($out, ['Generated', now('Australia/Hobart')->format('j M Y g:i a')]);
                fputcsv($out, []);

                fputcsv($out, [
                    'Date',
                    'Direction',
                    'Category',
                    'Description',
                    'Original Amount',
                    'Business Use %',
                    'Business Amount',
                    'GST',
                    'Net Ex GST',
                    'GST Treatment',
                    'Purchase Type',
                    'Payment Source',
                    'Source',
                    'Notes',
                ]);

                foreach ($transactions as $transaction) {
                    fputcsv($out, [
                        $transaction->transaction_date->format('Y-m-d'),
                        (float) $transaction->amount >= 0 ? 'Income' : 'Expense',
                        $transaction->category?->name ?? 'No category',
                        $transaction->description ?? '',
                        number_format((float) $transaction->amount, 2, '.', ''),
                        number_format((float) $transaction->business_use_percentage, 2, '.', ''),
                        number_format((float) $transaction->business_amount, 2, '.', ''),
                        number_format((float) $transaction->gst_amount, 2, '.', ''),
                        number_format((float) $transaction->net_amount, 2, '.', ''),
                        $transaction->gst_treatment ?? '',
                        $transaction->purchase_type ?? '',
                        $transaction->payment_source ?? '',
                        $transaction->source ?? '',
                        $transaction->notes ?? '',
                    ]);
                }

                fputcsv($out, []);
                fputcsv($out, ['Summary']);
                fputcsv($out, ['Transactions', $summary['count']]);
                fputcsv($out, ['Business amount total', number_format((float) $summary['amount'], 2, '.', '')]);
                fputcsv($out, ['GST total', number_format((float) $summary['gst'], 2, '.', '')]);
                fputcsv($out, ['Net ex GST total', number_format((float) $summary['net'], 2, '.', '')]);

                fclose($out);
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    protected function safeFilename(string $filename): string
    {
        return preg_replace('/[\\\\\/:*?"<>|]+/', '-', $filename) ?: 'Transaction Report.csv';
    }

    protected function filteredTransactionsQuery(): Builder
    {
        $businessId = $this->getBookBusiness()->id;

        return BookTransaction::query()
            ->where('book_business_id', $businessId)
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%' . trim($this->search) . '%';
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('description', 'like', $search)
                        ->orWhere('notes', 'like', $search)
                        ->orWhere('payment_source', 'like', $search);
                });
            })
            ->when($this->directionFilter === 'income', fn (Builder $query) => $query->where('amount', '>', 0))
            ->when($this->directionFilter === 'expense', fn (Builder $query) => $query->where('amount', '<', 0))
            ->when($this->categoryFilter, fn (Builder $query) => $query->where('book_category_id', $this->categoryFilter))
            ->when($this->financialYearFilter !== 'all', function (Builder $query): void {
                $financialYear = $this->financialYears->firstWhere('id', (int) $this->financialYearFilter);

                if (! $financialYear) {
                    return;
                }

                $start = $financialYear->start_date->copy();
                $end = $financialYear->end_date->copy();

                if (in_array($this->periodFilter, ['q1', 'q2', 'q3', 'q4'], true)) {
                    $quarterIndex = match ($this->periodFilter) {
                        'q1' => 0,
                        'q2' => 1,
                        'q3' => 2,
                        'q4' => 3,
                    };

                    $start = $financialYear->start_date->copy()->addMonths($quarterIndex * 3);
                    $end = $start->copy()->addMonths(3)->subDay();

                    if ($end->gt($financialYear->end_date)) {
                        $end = $financialYear->end_date->copy();
                    }
                }

                $query->whereBetween('transaction_date', [
                    $start->toDateString(),
                    $end->toDateString(),
                ]);
            });
    }

    public function getTransactionsProperty()
    {
        return $this->filteredTransactionsQuery()
            ->with(['category', 'bankAccount'])
            ->withCount('documents')
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();
    }

    public function getTransactionSummaryProperty(): array
    {
        $summary = $this->filteredTransactionsQuery()
            ->toBase()
            ->selectRaw('COUNT(*) as transaction_count')
            ->selectRaw('COALESCE(SUM(net_amount), 0) as net_total')
            ->selectRaw('COALESCE(SUM(gst_amount), 0) as gst_total')
            ->selectRaw('COALESCE(SUM(business_amount), 0) as amount_total')
            ->first();

        return [
            'count' => (int) ($summary->transaction_count ?? 0),
            'net' => (float) ($summary->net_total ?? 0),
            'gst' => (float) ($summary->gst_total ?? 0),
            'amount' => (float) ($summary->amount_total ?? 0),
        ];
    }

    public function getDocumentTransactionProperty(): ?BookTransaction
    {
        if (! $this->documentTransactionId) {
            return null;
        }

        return BookTransaction::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->find($this->documentTransactionId);
    }

    public function getTransactionDocumentsProperty()
    {
        if (! $this->documentTransactionId) {
            return collect();
        }

        return BookDocument::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->where('book_transaction_id', $this->documentTransactionId)
            ->orderByDesc('uploaded_at')
            ->orderByDesc('id')
            ->get();
    }

    public function getDocumentStorageReadyProperty(): bool
    {
        return app(BookDocumentStorage::class)->ready();
    }

    public function getDocumentStorageLabelProperty(): string
    {
        return app(BookDocumentStorage::class)->storageLabel();
    }

    public function openDocuments(int $transactionId): void
    {
        $transaction = $this->findAccessibleTransaction($transactionId);

        $this->documentTransactionId = $transaction->id;
        $this->documentPendingDeleteId = null;
        $this->documentUploads = [];
        $this->documentNotes = '';
        $this->resetValidation([
            'documentUploads',
            'documentUploads.*',
            'documentNotes',
            'document',
        ]);

        $this->dispatch('open-modal', id: 'bookTransactionDocuments');
    }

    public function uploadDocuments(): void
    {
        $transaction = $this->documentTransaction;

        abort_unless($transaction, 404);

        if (! $this->documentStorageReady) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'documentUploads' => 'DigitalOcean Spaces is not configured for Books.',
            ]);
        }

        $this->validate([
            'documentUploads' => ['required', 'array', 'min:1', 'max:10'],
            'documentUploads.*' => [
                'required',
                'file',
                'max:20480',
                'mimes:pdf,jpg,jpeg,png,webp,heic,heif',
            ],
            'documentNotes' => ['nullable', 'string', 'max:2000'],
        ], [
            'documentUploads.required' => 'Choose at least one receipt or document.',
            'documentUploads.*.max' => 'Each document must be 20 MB or smaller.',
            'documentUploads.*.mimes' => 'Receipts/documents must be PDF, JPG, PNG, WebP, HEIC or HEIF.',
        ]);

        $storage = app(BookDocumentStorage::class);

        foreach ($this->documentUploads as $upload) {
            $storage->storeTransactionDocument(
                $transaction,
                $upload,
                $this->documentNotes,
            );
        }

        $count = count($this->documentUploads);

        $this->documentUploads = [];
        $this->documentNotes = '';
        $this->resetValidation([
            'documentUploads',
            'documentUploads.*',
            'documentNotes',
        ]);

        Notification::make()
            ->success()
            ->title($count === 1 ? 'Document attached' : $count . ' documents attached')
            ->send();

        $this->dispatch('book-transaction-updated');
    }

    public function getPendingDeleteDocumentProperty(): ?BookDocument
    {
        if (! $this->documentPendingDeleteId) {
            return null;
        }

        return BookDocument::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->where('book_transaction_id', $this->documentTransactionId)
            ->find($this->documentPendingDeleteId);
    }

    public function openDeleteDocumentConfirmation(int $documentId): void
    {
        $document = $this->findAccessibleDocument($documentId);

        abort_unless(
            $this->documentTransactionId
            && (int) $document->book_transaction_id === (int) $this->documentTransactionId,
            404,
        );

        $this->documentPendingDeleteId = $document->id;

        $this->dispatch('open-modal', id: 'bookDocumentDeleteConfirmation');
    }

    public function cancelDeleteDocument(): void
    {
        $this->documentPendingDeleteId = null;

        $this->dispatch('close-modal', id: 'bookDocumentDeleteConfirmation');
    }

    public function confirmDeleteDocument(): void
    {
        $document = $this->pendingDeleteDocument;

        abort_unless($document, 404);

        $filename = $document->original_filename;

        app(BookDocumentStorage::class)->delete($document);

        $this->documentPendingDeleteId = null;

        $this->dispatch('close-modal', id: 'bookDocumentDeleteConfirmation');

        Notification::make()
            ->success()
            ->title('Document removed')
            ->body($filename)
            ->send();

        $this->dispatch('book-transaction-updated');
    }

    public function documentUrl(int $documentId): string
    {
        $document = $this->findAccessibleDocument($documentId);

        return app(BookDocumentStorage::class)->temporaryUrl($document);
    }

    public function addTransaction(): void
    {
        $this->resetEditor();
        $this->dispatch('open-modal', id: 'bookTransactionEditor');
    }

    public function editTransaction(int $transactionId): void
    {
        $transaction = $this->findAccessibleTransaction($transactionId);

        if ($lock = $this->transactionLockForDate($transaction->transaction_date)) {
            Notification::make()
                ->warning()
                ->title($lock['title'])
                ->body($lock['body'])
                ->send();

            return;
        }

        $this->editingTransactionId = $transaction->id;
        $this->transactionType = $transaction->amount >= 0 ? 'income' : 'expense';
        $this->transactionDate = $transaction->transaction_date->toDateString();
        $this->amountInput = number_format(abs((float) $transaction->amount), 2, '.', '');
        $this->editingCategoryId = $transaction->book_category_id;
        $this->description = $transaction->description ?? '';
        $this->paymentSource = $transaction->source === 'bank_import'
            ? 'Business bank account'
            : ($transaction->payment_source ?? 'Business bank account');
        $this->businessUsePercentage = (float) $transaction->business_use_percentage;
        $this->gstTreatment = $transaction->gst_treatment ?: 'gst_applicable';
        $this->includesGst = $this->gstTreatment === 'gst_applicable' && $this->gstRegistered;
        $this->excludeFromBas = $this->gstTreatment === 'excluded';
        $this->gstAmountInput = number_format((float) $transaction->gst_amount, 2, '.', '');
        $this->gstAmountOverridden = true;
        $this->purchaseType = $transaction->purchase_type ?: 'non_capital';
        $this->notes = $transaction->notes ?? '';
        $this->transactionSource = $transaction->source ?: 'manual';

        $this->dispatch('open-modal', id: 'bookTransactionEditor');
    }

    public function updatedTransactionType(): void
    {
        if ($this->editingTransactionId) {
            return;
        }

        // Categories are direction-specific. Clear any previous choice when
        // switching a new transaction between Expense and Income.
        $this->editingCategoryId = null;
    }

    public function updatedEditingCategoryId($categoryId): void
    {
        if (! $categoryId) {
            return;
        }

        $category = BookCategory::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->find($categoryId);

        if (! $category) {
            return;
        }

        $this->businessUsePercentage = (float) $category->default_business_use_percentage;
        $this->gstTreatment = $category->default_gst_treatment;
        $this->includesGst = $this->gstTreatment === 'gst_applicable' && $this->gstRegistered;
        $this->excludeFromBas = $this->gstTreatment === 'excluded';
        $this->gstAmountOverridden = false;
        $this->purchaseType = $category->default_purchase_type;
        $this->refreshAutoGstAmount();
    }

    public function updatedAmountInput(): void
    {
        if (! $this->gstAmountOverridden) {
            $this->refreshAutoGstAmount();
        }
    }

    public function updatedBusinessUsePercentage(): void
    {
        if (! $this->gstAmountOverridden) {
            $this->refreshAutoGstAmount();
        }
    }

    public function updatedIncludesGst(bool $includesGst): void
    {
        if ($includesGst) {
            $this->excludeFromBas = false;
        }

        $this->gstAmountOverridden = false;
        $this->refreshAutoGstAmount();
    }

    public function updatedExcludeFromBas(bool $excludeFromBas): void
    {
        if ($excludeFromBas) {
            $this->includesGst = false;
        }

        $this->gstAmountOverridden = false;
        $this->refreshAutoGstAmount();
    }

    public function updatedGstAmountInput(): void
    {
        if ($this->gstRegistered && $this->includesGst && ! $this->excludeFromBas) {
            $this->gstAmountOverridden = true;
        }
    }

    public function recalculateGstAmount(): void
    {
        $this->gstAmountOverridden = false;
        $this->refreshAutoGstAmount();
    }

    public function updateCategory(int $transactionId, $categoryId): void
    {
        $transaction = $this->findAccessibleTransaction($transactionId);

        if ($lock = $this->transactionLockForDate($transaction->transaction_date)) {
            Notification::make()
                ->warning()
                ->title($lock['title'])
                ->body($lock['body'])
                ->send();

            return;
        }

        $categoryId = filled($categoryId) ? (int) $categoryId : null;

        if ($categoryId) {
            $direction = $transaction->amount >= 0 ? 'income' : 'expense';
            $categoryExists = BookCategory::query()
                ->where('book_business_id', $this->getBookBusiness()->id)
                ->whereKey($categoryId)
                ->whereIn('type', [$direction, 'other'])
                ->exists();

            abort_unless($categoryExists, 403);
        }

        $transaction->update(['book_category_id' => $categoryId]);

        $this->dispatch('book-transaction-updated');
    }

    public function saveTransaction(): void
    {
        $validated = $this->validate([
            'transactionType' => ['required', 'in:income,expense'],
            'transactionDate' => ['required', 'date'],
            'amountInput' => ['required', 'numeric', 'min:0.01'],
            'editingCategoryId' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:2000'],
            'paymentSource' => ['nullable', 'string', 'max:50'],
            'businessUsePercentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'includesGst' => ['required', 'boolean'],
            'excludeFromBas' => ['required', 'boolean'],
            'gstAmountInput' => ['nullable', 'numeric', 'min:0'],
            'purchaseType' => ['required', 'in:non_capital,capital'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $business = $this->getBookBusiness();

        $editingTransaction = null;

        if ($this->editingTransactionId) {
            $editingTransaction = $this->findAccessibleTransaction($this->editingTransactionId);
            $this->assertTransactionIsEditable($editingTransaction);

            // A transaction's direction is fixed after creation.
            // Preserve the original sign even if the Livewire property is manipulated.
            $validated['transactionType'] = $editingTransaction->amount >= 0 ? 'income' : 'expense';
            $this->transactionType = $validated['transactionType'];
        }

        $this->assertDateIsEditable($validated['transactionDate']);

        if ($this->editingCategoryId) {
            $categoryExists = BookCategory::query()
                ->where('book_business_id', $business->id)
                ->whereKey($this->editingCategoryId)
                ->whereIn('type', [$validated['transactionType'], 'other'])
                ->exists();

            abort_unless($categoryExists, 403);
        }

        $amount = abs((float) $validated['amountInput']);
        $signedAmount = $validated['transactionType'] === 'expense' ? -$amount : $amount;
        $businessUse = (float) $validated['businessUsePercentage'];
        $businessAmount = round($signedAmount * ($businessUse / 100), 4);

        $gstTreatment = ! $business->gst_registered
            ? 'gst_free'
            : ($validated['excludeFromBas']
                ? 'excluded'
                : ($validated['includesGst'] ? 'gst_applicable' : 'gst_free'));

        $gstAmount = 0.0;

        if ($gstTreatment === 'gst_applicable') {
            $gstAmount = $validated['gstAmountInput'] !== null
                ? round((float) $validated['gstAmountInput'], 2)
                : $this->autoGstAmount($amount, $businessUse, (float) $business->gst_rate);

            if ($gstAmount > abs($businessAmount) + 0.005) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'gstAmountInput' => 'GST cannot be greater than the business-use amount.',
                ]);
            }
        }

        $netAbsolute = round(max(0, abs($businessAmount) - $gstAmount), 2);
        $netAmount = $businessAmount < 0 ? -$netAbsolute : $netAbsolute;

        // Bank imports always represent money moving through the business bank
        // account. Do not allow a Livewire/client-side change to turn an imported
        // transaction into Personal funds, Cash or Other.
        $paymentSource = $editingTransaction?->source === 'bank_import'
            ? 'Business bank account'
            : ($validated['paymentSource'] ?: null);

        $data = [
            'book_business_id' => $business->id,
            'book_category_id' => $this->editingCategoryId,
            'transaction_date' => $validated['transactionDate'],
            'amount' => $signedAmount,
            'business_use_percentage' => $validated['businessUsePercentage'],
            'business_amount' => $businessAmount,
            'net_amount' => $netAmount,
            'gst_amount' => $gstAmount,
            'gst_treatment' => $gstTreatment,
            'sale_type' => $validated['transactionType'] === 'income'
                ? match ($gstTreatment) {
                    'gst_applicable' => 'gst',
                    'gst_free' => 'gst_free',
                    default => 'excluded',
                }
                : null,
            'purchase_type' => $validated['transactionType'] === 'expense' ? $validated['purchaseType'] : null,
            'source' => $this->editingTransactionId ? null : 'manual',
            'payment_source' => $paymentSource,
            'description' => $validated['description'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ];

        if ($this->editingTransactionId) {
            unset($data['source']);
            $editingTransaction->update($data);
            $message = 'Transaction updated';
        } else {
            BookTransaction::create($data);
            $message = 'Transaction added';
        }

        $this->dispatch('close-modal', id: 'bookTransactionEditor');
        $this->dispatch('book-transaction-updated');

        Notification::make()->success()->title($message)->send();
        $this->resetEditor();
    }

    protected function setDefaultFinancialYearFilter(): void
    {
        $today = now('Australia/Hobart')->toDateString();

        $currentFinancialYear = BookFinancialYear::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderByDesc('start_date')
            ->first();

        $this->financialYearFilter = $currentFinancialYear
            ? (string) $currentFinancialYear->id
            : 'all';
    }

    public function closedFinancialYearForDate(mixed $date): ?BookFinancialYear
    {
        if (blank($date)) {
            return null;
        }

        $dateString = $date instanceof \Carbon\CarbonInterface
            ? $date->toDateString()
            : \Carbon\Carbon::parse($date)->toDateString();

        return $this->financialYears
            ->first(fn (BookFinancialYear $financialYear) =>
                $financialYear->status === 'closed'
                && $dateString >= $financialYear->start_date->toDateString()
                && $dateString <= $financialYear->end_date->toDateString()
            );
    }

    public function lodgedBasPeriodForDate(mixed $date): ?BookBasPeriod
    {
        if (blank($date)) {
            return null;
        }

        $dateString = $date instanceof \Carbon\CarbonInterface
            ? $date->toDateString()
            : \Carbon\Carbon::parse($date)->toDateString();

        return $this->lodgedBasPeriods
            ->first(fn (BookBasPeriod $period) =>
                $dateString >= $period->start_date->toDateString()
                && $dateString <= $period->end_date->toDateString()
            );
    }

    public function transactionLockForDate(mixed $date): ?array
    {
        if ($financialYear = $this->closedFinancialYearForDate($date)) {
            return [
                'type' => 'financial_year',
                'title' => $financialYear->label . ' is closed',
                'body' => 'Reopen the financial year before changing this transaction.',
            ];
        }

        if ($basPeriod = $this->lodgedBasPeriodForDate($date)) {
            return [
                'type' => 'bas',
                'title' => $basPeriod->period_label . ' BAS is lodged',
                'body' => 'Reopen that BAS period before changing transactions in it.',
            ];
        }

        return null;
    }

    protected function assertDateIsEditable(mixed $date): void
    {
        if ($financialYear = $this->closedFinancialYearForDate($date)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'transactionDate' => $financialYear->label . ' is closed. Reopen the financial year before adding or moving a transaction into it.',
            ]);
        }

        if ($basPeriod = $this->lodgedBasPeriodForDate($date)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'transactionDate' => $basPeriod->period_label . ' BAS is lodged. Reopen that BAS period before adding or moving a transaction into it.',
            ]);
        }
    }

    protected function assertTransactionIsEditable(BookTransaction $transaction): void
    {
        if ($financialYear = $this->closedFinancialYearForDate($transaction->transaction_date)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'transactionDate' => $financialYear->label . ' is closed. Reopen the financial year before editing this transaction.',
            ]);
        }

        if ($basPeriod = $this->lodgedBasPeriodForDate($transaction->transaction_date)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'transactionDate' => $basPeriod->period_label . ' BAS is lodged. Reopen that BAS period before editing this transaction.',
            ]);
        }
    }

    protected function refreshAutoGstAmount(): void
    {
        if (! $this->gstRegistered || ! $this->includesGst || $this->excludeFromBas) {
            $this->gstAmountInput = '0.00';

            return;
        }

        if (blank($this->amountInput) || (float) $this->amountInput <= 0) {
            $this->gstAmountInput = null;

            return;
        }

        $this->gstAmountInput = number_format(
            $this->autoGstAmount(
                abs((float) $this->amountInput),
                (float) $this->businessUsePercentage,
                (float) $this->getBookBusiness()->gst_rate,
            ),
            2,
            '.',
            ''
        );
    }

    protected function autoGstAmount(float $grossAmount, float $businessUsePercentage, float $gstRate): float
    {
        if ($gstRate <= 0) {
            return 0.0;
        }

        $businessGross = abs($grossAmount) * ($businessUsePercentage / 100);

        return round($businessGross * ($gstRate / (100 + $gstRate)), 2);
    }

    protected function findAccessibleTransaction(int $transactionId): BookTransaction
    {
        return BookTransaction::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->findOrFail($transactionId);
    }

    protected function findAccessibleDocument(int $documentId): BookDocument
    {
        return BookDocument::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->whereNotNull('book_transaction_id')
            ->findOrFail($documentId);
    }

    protected function resetEditor(): void
    {
        $this->resetValidation();
        $this->editingTransactionId = null;
        $this->transactionType = 'expense';
        $this->transactionDate = now('Australia/Hobart')->toDateString();
        $this->amountInput = null;
        $this->editingCategoryId = null;
        $this->description = '';
        $this->paymentSource = 'Business bank account';
        $this->businessUsePercentage = 100;
        $this->gstTreatment = $this->gstRegistered ? 'gst_applicable' : 'gst_free';
        $this->includesGst = $this->gstRegistered;
        $this->excludeFromBas = false;
        $this->gstAmountInput = null;
        $this->gstAmountOverridden = false;
        $this->purchaseType = 'non_capital';
        $this->notes = '';
        $this->transactionSource = 'manual';
    }
}
