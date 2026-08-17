<x-filament-panels::page>
    <style>
        .books-transaction-print {
            display: none;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm 9mm 10mm;
            }

            body *:not(#books-transaction-print):not(#books-transaction-print *):not(:has(#books-transaction-print)) {
                display: none !important;
            }

            #books-transaction-print {
                display: block !important;
                position: static !important;
                width: 100% !important;
                background: #fff !important;
                color: #111827 !important;
                font-family: Arial, Helvetica, sans-serif !important;
                font-size: 7pt !important;
                line-height: 1.12 !important;
            }

            #books-transaction-print .trp-header {
                display: flex;
                justify-content: space-between;
                gap: 20px;
                padding-bottom: 7px;
                border-bottom: 2px solid #111827;
                margin-bottom: 8px;
            }

            #books-transaction-print .trp-business {
                font-size: 11.5pt;
                font-weight: 700;
            }

            #books-transaction-print .trp-title {
                text-align: right;
                font-size: 13.5pt;
                font-weight: 700;
            }

            #books-transaction-print .trp-period {
                margin-top: 2px;
                text-align: right;
                font-size: 7.8pt;
                font-weight: 600;
            }

            #books-transaction-print .trp-meta {
                color: #4b5563 !important;
                font-size: 6.6pt !important;
            }

            #books-transaction-print .trp-summary {
                display: grid;
                grid-template-columns: 1.2fr 1fr 1fr 1fr;
                border: 1px solid #d1d5db;
                margin: 0 0 7px;
            }

            #books-transaction-print .trp-summary > div {
                padding: 5px 7px;
                border-right: 1px solid #d1d5db;
            }

            #books-transaction-print .trp-summary > div:last-child {
                border-right: 0;
            }

            #books-transaction-print .trp-summary-label {
                color: #6b7280 !important;
                font-size: 6pt;
                text-transform: uppercase;
                letter-spacing: .03em;
            }

            #books-transaction-print .trp-summary-value {
                margin-top: 2px;
                font-size: 8.6pt;
                font-weight: 700;
                font-variant-numeric: tabular-nums;
            }

            #books-transaction-print table {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
            }

            #books-transaction-print thead {
                display: table-header-group;
            }

            #books-transaction-print th {
                padding: 3px 4px;
                border-bottom: 1.5px solid #111827;
                color: #4b5563 !important;
                font-size: 6.2pt;
                text-align: left;
            }

            #books-transaction-print td {
                padding: 3px 4px;
                border-bottom: 1px solid #e5e7eb;
                vertical-align: top;
            }

            #books-transaction-print .trp-date {
                width: 9%;
                white-space: nowrap;
            }

            #books-transaction-print .trp-category {
                width: 17%;
            }

            #books-transaction-print .trp-description {
                width: 38%;
            }

            #books-transaction-print .trp-money {
                width: 12%;
                text-align: right;
                white-space: nowrap;
                font-variant-numeric: tabular-nums;
            }

            #books-transaction-print tr {
                break-inside: avoid;
            }

            #books-transaction-print .trp-footer {
                margin-top: 8px;
                padding-top: 5px;
                border-top: 1px solid #d1d5db;
                color: #6b7280 !important;
                font-size: 6pt !important;
            }
        }
    </style>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Transactions</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Live business transactions used by reports and BAS.</p>
        </div>

        <div class="flex shrink-0 items-center gap-2 whitespace-nowrap">
            <x-filament::button wire:click="addTransaction" icon="heroicon-o-plus">Add Transaction</x-filament::button>
            <button type="button" data-print-title="{{ ($this->getBookBusiness()->legal_name ?: $this->getBookBusiness()->name) . ' - Transaction Report' }}"
                onclick="const oldTitle = document.title; document.title = this.dataset.printTitle; window.addEventListener('afterprint', () => document.title = oldTitle, { once: true }); window.print();"
                class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg bg-[#e87000] px-3 text-sm font-medium text-white shadow-sm transition hover:bg-[#d86700] focus:outline-none focus:ring-2 focus:ring-[#e87000]/40">
                <x-heroicon-o-printer class="h-4 w-4"/>
                Print
            </button>

            <button type="button" wire:click="exportCsv" wire:loading.attr="disabled" wire:target="exportCsv"
                class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg bg-[#e87000] px-3 text-sm font-medium text-white shadow-sm transition hover:bg-[#d86700] focus:outline-none focus:ring-2 focus:ring-[#e87000]/40 disabled:opacity-50">
                <x-heroicon-o-arrow-down-tray class="h-4 w-4"/>
                <span wire:loading.remove wire:target="exportCsv">Export</span>
                <span wire:loading wire:target="exportCsv">Exporting…</span>
            </button>
        </div>
    </div>

    <div class="relative z-30 mt-6 flex flex-col gap-3 lg:flex-row lg:items-center">
        <x-books.select width="w-full lg:w-48" model="directionFilter" :value="$directionFilter" :options="['all' => 'Income & expenses', 'income' => 'Income only', 'expense' => 'Expenses only']"/>
        <x-books.select width="w-full lg:w-40" model="financialYearFilter" :value="$financialYearFilter" :options="$this->financialYearOptions"/>

        <div
            @class([
                'inline-flex h-10 w-fit shrink-0 items-center rounded-lg bg-gray-100 p-1 ring-1 ring-gray-950/10 dark:bg-white/5 dark:ring-white/10',
                'opacity-50' => $financialYearFilter === 'all',
            ])
            title="{{ $financialYearFilter === 'all' ? 'Select a financial year to filter by quarter' : 'Filter the selected financial year by BAS quarter' }}"
        >
            @foreach (['all' => 'All', 'q1' => 'Q1', 'q2' => 'Q2', 'q3' => 'Q3', 'q4' => 'Q4'] as $period => $label)
                <button
                    type="button"
                    wire:click="$set('periodFilter', '{{ $period }}')"
                    @disabled($financialYearFilter === 'all' && $period !== 'all')
                    @class([
                        'min-w-11 rounded-md px-3 py-1.5 text-sm font-medium transition',
                        'bg-[#e87000] text-white shadow-sm' => $periodFilter === $period,
                        'text-gray-600 hover:bg-white/70 hover:text-gray-950 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-white' => $periodFilter !== $period,
                        'cursor-not-allowed' => $financialYearFilter === 'all' && $period !== 'all',
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <x-books.select width="w-full lg:w-56" model="categoryFilter" :value="$categoryFilter" allow-empty empty-label="All categories"
                        :options="$this->categories->mapWithKeys(fn ($category) => [$category->id => $category->name . ($category->active ? '' : ' (inactive)')])->all()"/>

        <div class="min-w-0 flex-1">
            <x-filament::input.wrapper>
                <x-filament::input wire:model.live.debounce.300ms="search" placeholder="Search description, notes or payment source..."/>
            </x-filament::input.wrapper>
        </div>
    </div>

    <div
        wire:ignore.self
        x-data="{
            viewportGap: 32,
            panelHeight: 280,
            resizePanel() {
                this.panelHeight = Math.max(
                    280,
                    window.innerHeight - this.$el.getBoundingClientRect().top - this.viewportGap
                )
            },
        }"
        x-init="$nextTick(() => resizePanel())"
        x-on:resize.window="resizePanel()"
        x-effect="
            $wire.search;
            $wire.directionFilter;
            $wire.categoryFilter;
            $wire.financialYearFilter;
            $wire.periodFilter;
            $nextTick(() => resizePanel());
        "
        x-bind:style="`height: ${panelHeight}px; display: grid; grid-template-rows: minmax(0, 1fr) auto;`"
        class="relative z-0 mt-5 min-h-[17.5rem] overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900"
    >
        <div class="overflow-auto" style="min-height: 0;">
            <table class="w-full min-w-[980px] text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 shadow-sm dark:bg-gray-900 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3 w-56">Category</th>
                    <th class="px-4 py-3 w-40 text-right">Amount</th>
                    <th class="px-4 py-3 w-32 text-right">GST</th>
                    <th class="px-4 py-3 w-36 text-right">Nett GST</th>
                    <th class="px-4 py-3">Description</th>
                    <th class="px-4 py-3 w-28"></th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @forelse ($this->transactions as $transaction)
                    @php($lockedFinancialYear = $this->closedFinancialYearForDate($transaction->transaction_date))
                    <tr wire:key="book-transaction-{{ $transaction->id }}" class="hover:bg-gray-50/80 dark:hover:bg-white/[0.03]">
                        <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">
                            {{ $transaction->transaction_date->format('j M Y') }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($lockedFinancialYear)
                                <span class="text-gray-700 dark:text-gray-200">{{ $transaction->category?->name ?? 'No category' }}</span>
                            @else
                                <x-books.select wire:key="transaction-category-{{ $transaction->id }}-{{ $transaction->book_category_id ?? 'none' }}" :value="$transaction->book_category_id"
                                                :options="$this->categories->filter(fn ($category) => $category->active || $category->id === $transaction->book_category_id)->whereIn('type', [$transaction->amount >= 0 ? 'income' : 'expense', 'other'])->pluck('name', 'id')->all()"
                                                action="updateCategory" :action-args="[$transaction->id]" allow-empty empty-label="No category"
                                />
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold {{ $transaction->business_amount >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-950 dark:text-white' }}">
                            {{ $transaction->business_amount >= 0 ? '+' : '-' }}${{ number_format(abs((float) $transaction->business_amount), 2) }}
                            @if ((float) $transaction->business_use_percentage < 100)
                                <div class="mt-0.5 text-[10px] font-normal text-primary-500">
                                    {{ number_format((float) $transaction->business_use_percentage, 0) }}% of
                                    {{ $transaction->amount >= 0 ? '+' : '-' }}${{ number_format(abs((float) $transaction->amount), 2) }}
                                </div>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-gray-500 dark:text-gray-400">
                            {{ (float) $transaction->gst_amount > 0 ? '$' . number_format((float) $transaction->gst_amount, 2) : '—' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-medium {{ (float) $transaction->net_amount >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-700 dark:text-gray-200' }}">
                            {{ (float) $transaction->net_amount >= 0 ? '+' : '-' }}${{ number_format(abs((float) $transaction->net_amount), 2) }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($lockedFinancialYear)
                                <div class="font-medium text-gray-950 dark:text-white">
                                    {{ $transaction->description ?: 'No description' }}
                                </div>
                            @else
                                <button wire:click="editTransaction({{ $transaction->id }})" class="text-left font-medium text-gray-950 hover:text-primary-600 dark:text-white dark:hover:text-primary-400">
                                    {{ $transaction->description ?: 'No description' }}
                                </button>
                            @endif
                            @if ($transaction->payment_source)
                                <div class="mt-0.5 text-xs text-gray-400">{{ $transaction->payment_source }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button
                                    type="button"
                                    wire:click.stop="openDocuments({{ $transaction->id }})"
                                    @class([
                                        'relative rounded-lg p-2 transition hover:bg-gray-100 dark:hover:bg-white/5',
                                        'text-[#e87000]' => $transaction->documents_count > 0,
                                        'text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' => $transaction->documents_count === 0,
                                    ])
                                    title="{{ $transaction->documents_count ? $transaction->documents_count . ' attached ' . \Illuminate\Support\Str::plural('document', $transaction->documents_count) : 'Attach receipt/document' }}"
                                >
                                    <x-heroicon-o-paper-clip class="h-4 w-4"/>
                                    @if ($transaction->documents_count > 0)
                                        <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-[#e87000] px-1 text-[9px] font-bold leading-none text-white">
                                            {{ $transaction->documents_count > 9 ? '9+' : $transaction->documents_count }}
                                        </span>
                                    @endif
                                </button>

                                @if ($lockedFinancialYear)
                                    <span class="inline-flex rounded-lg p-2 text-danger-500 dark:text-danger-400" title="{{ $lockedFinancialYear->label }} is closed">
                                        <x-heroicon-o-lock-closed class="h-4 w-4"/>
                                    </span>
                                @else
                                    <button wire:click="editTransaction({{ $transaction->id }})" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-gray-200" title="Edit">
                                        <x-heroicon-o-pencil-square class="h-4 w-4"/>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-14 text-center text-gray-500 dark:text-gray-400">
                            No transactions yet. Add one manually or import a bank CSV when the import workflow is added.
                        </td>
                    </tr>
                @endforelse
                </tbody>

            </table>
        </div>

        <div class="border-t border-gray-200 bg-gray-50 px-4 py-3 shadow-[0_-1px_3px_rgba(0,0,0,0.08)] dark:border-white/10 dark:bg-gray-900" style="grid-row: 2;">
            <div class="grid grid-cols-[minmax(0,1fr)_9rem_8rem_10rem_4rem] items-center">
                <div class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                    {{ number_format($this->transactionSummary['count']) }}
                    {{ \Illuminate\Support\Str::plural('transaction', $this->transactionSummary['count']) }}
                </div>

                <div class="whitespace-nowrap pr-4 text-right">
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Nett GST total</div>
                    <div class="font-semibold {{ $this->transactionSummary['net'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-700 dark:text-gray-200' }}">
                        {{ $this->transactionSummary['net'] >= 0 ? '+' : '-' }}${{ number_format(abs($this->transactionSummary['net']), 2) }}
                    </div>
                </div>

                <div class="whitespace-nowrap pr-4 text-right">
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">GST total</div>
                    <div class="font-semibold text-gray-700 dark:text-gray-200">
                        ${{ number_format($this->transactionSummary['gst'], 2) }}
                    </div>
                </div>

                <div class="whitespace-nowrap pr-4 text-right">
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Amount total</div>
                    <div class="font-semibold {{ $this->transactionSummary['amount'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-950 dark:text-white' }}">
                        {{ $this->transactionSummary['amount'] >= 0 ? '+' : '-' }}${{ number_format(abs($this->transactionSummary['amount']), 2) }}
                    </div>
                </div>

                <div></div>
            </div>
        </div>
    </div>


    {{-- Dedicated white-paper transaction report using the CURRENT screen filters --}}
    @php($printBusiness = $this->getBookBusiness())
    @php($printSummary = $this->transactionSummary)
    <section id="books-transaction-print" class="books-transaction-print">
        <div class="trp-header">
            <div>
                <div class="trp-business">{{ $printBusiness->legal_name ?: $printBusiness->name }}</div>
                @if ($printBusiness->legal_name && $printBusiness->legal_name !== $printBusiness->name)
                    <div class="trp-meta">Trading as {{ $printBusiness->name }}</div>
                @endif
                @if ($printBusiness->abn)
                    <div class="trp-meta">ABN {{ $printBusiness->abn }}</div>
                @endif
            </div>

            <div>
                <div class="trp-title">Transaction Report</div>
                <div class="trp-period">{{ $this->filteredPeriodLabel }}</div>
                <div class="trp-meta" style="text-align:right; margin-top:2px;">
                    Generated {{ now('Australia/Hobart')->format('j M Y') }}
                </div>
            </div>
        </div>

        <div class="trp-summary">
            <div>
                <div class="trp-summary-label">Transactions</div>
                <div class="trp-summary-value">{{ number_format($printSummary['count']) }}</div>
            </div>
            <div>
                <div class="trp-summary-label">Business amount</div>
                <div class="trp-summary-value">
                    {{ $printSummary['amount'] >= 0 ? '+' : '-' }}${{ number_format(abs($printSummary['amount']), 2) }}
                </div>
            </div>
            <div>
                <div class="trp-summary-label">GST total</div>
                <div class="trp-summary-value">${{ number_format($printSummary['gst'], 2) }}</div>
            </div>
            <div>
                <div class="trp-summary-label">Net ex GST</div>
                <div class="trp-summary-value">
                    {{ $printSummary['net'] >= 0 ? '+' : '-' }}${{ number_format(abs($printSummary['net']), 2) }}
                </div>
            </div>
        </div>

        <div class="trp-meta" style="margin-bottom:6px;">
            {{ $this->filterDescription }}
        </div>

        <table>
            <thead>
                <tr>
                    <th class="trp-date">Date</th>
                    <th class="trp-category">Category</th>
                    <th class="trp-description">Description</th>
                    <th class="trp-money">Business amount</th>
                    <th class="trp-money">GST</th>
                    <th class="trp-money">Net ex GST</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->transactions as $transaction)
                    <tr>
                        <td class="trp-date">{{ $transaction->transaction_date->format('j M Y') }}</td>
                        <td class="trp-category">
                            {{ $transaction->category?->name ?? 'No category' }}
                            @if ($transaction->category && ! $transaction->category->active)
                                <span style="color:#6b7280;"> (inactive)</span>
                            @endif
                        </td>
                        <td class="trp-description">
                            {{ $transaction->description ?: 'No description' }}
                            @if ((float) $transaction->business_use_percentage < 100)
                                <span style="color:#6b7280;"> · {{ number_format((float) $transaction->business_use_percentage, 0) }}% business use</span>
                            @endif
                            @if ($transaction->payment_source)
                                <span style="color:#6b7280;"> · {{ $transaction->payment_source }}</span>
                            @endif
                        </td>
                        <td class="trp-money">
                            {{ (float) $transaction->business_amount >= 0 ? '+' : '-' }}${{ number_format(abs((float) $transaction->business_amount), 2) }}
                        </td>
                        <td class="trp-money">
                            {{ (float) $transaction->gst_amount > 0 ? '$' . number_format((float) $transaction->gst_amount, 2) : '—' }}
                        </td>
                        <td class="trp-money">
                            {{ (float) $transaction->net_amount >= 0 ? '+' : '-' }}${{ number_format(abs((float) $transaction->net_amount), 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="trp-footer">
            Prepared from ClientBill Books transaction records for {{ $printBusiness->legal_name ?: $printBusiness->name }}.
            The printed rows and CSV export use the same filters currently selected on the Transactions screen.
        </div>
    </section>

    <x-filament::modal id="bookTransactionDocuments" width="3xl">
        <x-slot name="heading">
            Receipts &amp; documents
        </x-slot>

        @if ($this->documentTransaction)
            @php($documentTransaction = $this->documentTransaction)

            <div class="space-y-5">
                <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                {{ $documentTransaction->transaction_date->format('j M Y') }}
                                · {{ $documentTransaction->category?->name ?? 'No category' }}
                            </div>
                            <div class="mt-1 truncate font-semibold text-gray-950 dark:text-white">
                                {{ $documentTransaction->description ?: 'No description' }}
                            </div>
                        </div>

                        <div class="whitespace-nowrap text-lg font-bold tabular-nums {{ (float) $documentTransaction->business_amount >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-950 dark:text-white' }}">
                            {{ (float) $documentTransaction->business_amount >= 0 ? '+' : '-' }}${{ number_format(abs((float) $documentTransaction->business_amount), 2) }}
                        </div>
                    </div>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <div>
                            <div class="font-semibold text-gray-950 dark:text-white">Attached documents</div>
                            <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                Private files. View links expire automatically.
                            </div>
                        </div>

                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">
                            {{ $this->transactionDocuments->count() }}
                            {{ \Illuminate\Support\Str::plural('file', $this->transactionDocuments->count()) }}
                        </span>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                        @forelse ($this->transactionDocuments as $document)
                            <div class="flex items-center gap-3 border-b border-gray-100 px-4 py-3 last:border-b-0 dark:border-white/5">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-300">
                                    @if (str_starts_with((string) $document->mime_type, 'image/'))
                                        <x-heroicon-o-photo class="h-5 w-5"/>
                                    @else
                                        <x-heroicon-o-document-text class="h-5 w-5"/>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <a
                                        href="{{ $this->documentUrl($document->id) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="block truncate font-medium text-gray-950 hover:text-[#e87000] dark:text-white"
                                    >
                                        {{ $document->original_filename }}
                                    </a>

                                    <div class="mt-0.5 text-xs text-gray-400">
                                        @if ($document->size)
                                            {{ $document->size >= 1048576
                                                ? number_format($document->size / 1048576, 1) . ' MB'
                                                : number_format($document->size / 1024, 0) . ' KB' }}
                                            ·
                                        @endif
                                        {{ optional($document->uploaded_at)->timezone('Australia/Hobart')->format('j M Y g:i a') }}
                                        @if ($document->notes)
                                            · {{ $document->notes }}
                                        @endif
                                    </div>
                                </div>

                                <a
                                    href="{{ $this->documentUrl($document->id) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-gray-200"
                                    title="Open document"
                                >
                                    <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4"/>
                                </a>

                                <button
                                    type="button"
                                    wire:click="openDeleteDocumentConfirmation({{ $document->id }})"
                                    class="rounded-lg p-2 text-gray-400 transition hover:bg-danger-50 hover:text-danger-600 dark:hover:bg-danger-500/10 dark:hover:text-danger-400"
                                    title="Remove document"
                                >
                                    <x-heroicon-o-trash class="h-4 w-4"/>
                                </button>
                            </div>
                        @empty
                            <div class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No receipt or source document is attached yet.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="font-semibold text-gray-950 dark:text-white">Attach receipt/document</div>
                            <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                PDF or image · up to 20 MB each · maximum 10 files at once.
                            </div>
                        </div>

                        <span @class([
                            'inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-medium',
                            'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-300' => $this->documentStorageReady,
                            'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-300' => ! $this->documentStorageReady,
                        ])>
                            {{ $this->documentStorageLabel }}
                        </span>
                    </div>

                    @if (! $this->documentStorageReady)
                        <div class="mt-3 rounded-lg bg-warning-50 px-3 py-2 text-xs text-warning-800 dark:bg-warning-500/10 dark:text-warning-200">
                            Set the BOOKS_SPACES_* values in production before uploading financial documents.
                        </div>
                    @endif

                    <div class="mt-4">
                        <input
                            type="file"
                            wire:model="documentUploads"
                            multiple
                            accept=".pdf,.jpg,.jpeg,.png,.webp,.heic,.heif"
                            @disabled(! $this->documentStorageReady)
                            class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200 dark:text-gray-300 dark:file:bg-white/10 dark:file:text-gray-200 dark:hover:file:bg-white/15"
                        >
                        @error('documentUploads')
                            <div class="mt-1 text-xs text-danger-600">{{ $message }}</div>
                        @enderror
                        @error('documentUploads.*')
                            <div class="mt-1 text-xs text-danger-600">{{ $message }}</div>
                        @enderror

                        <div wire:loading wire:target="documentUploads" class="mt-2 text-xs font-medium text-[#e87000]">
                            Preparing document…
                        </div>

                        @if ($documentUploads)
                            <div class="mt-3 space-y-1">
                                @foreach ($documentUploads as $upload)
                                    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                        <x-heroicon-o-paper-clip class="h-3.5 w-3.5"/>
                                        <span class="truncate">{{ $upload->getClientOriginalName() }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="mt-4">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                            Document note <span class="font-normal text-gray-400">(optional)</span>
                        </label>
                        <x-filament::input.wrapper>
                            <x-filament::input
                                wire:model="documentNotes"
                                placeholder="e.g. Tax invoice emailed by supplier"
                                :disabled="! $this->documentStorageReady"
                            />
                        </x-filament::input.wrapper>
                        @error('documentNotes')
                            <div class="mt-1 text-xs text-danger-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-4 flex justify-end">
                        <x-filament::button
                            wire:click="uploadDocuments"
                            wire:loading.attr="disabled"
                            wire:target="uploadDocuments,documentUploads"
                            icon="heroicon-o-arrow-up-tray"
                            :disabled="! $this->documentStorageReady"
                        >
                            <span wire:loading.remove wire:target="uploadDocuments">Upload</span>
                            <span wire:loading wire:target="uploadDocuments">Uploading…</span>
                        </x-filament::button>
                    </div>
                </div>

                <div class="flex justify-end border-t border-gray-200 pt-4 dark:border-white/10">
                    <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'bookTransactionDocuments' })">
                        Close
                    </x-filament::button>
                </div>
            </div>
        @endif
    </x-filament::modal>

    <x-filament::modal
        id="bookDocumentDeleteConfirmation"
        width="md"
        :close-by-clicking-away="false"
    >
        @if ($this->pendingDeleteDocument)
            @php($deleteDocument = $this->pendingDeleteDocument)

            <div class="space-y-5">
                <div class="flex gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-danger-50 text-danger-600 dark:bg-danger-500/10 dark:text-danger-400">
                        <x-heroicon-o-trash class="h-5 w-5"/>
                    </div>

                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                            Remove this document?
                        </h3>
                        <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">
                            The attachment will be permanently removed from private storage.
                            The transaction itself will not be changed.
                        </p>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/[0.03]">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-gray-500 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/10 dark:text-gray-300 dark:ring-white/10">
                            @if (str_starts_with((string) $deleteDocument->mime_type, 'image/'))
                                <x-heroicon-o-photo class="h-5 w-5"/>
                            @else
                                <x-heroicon-o-document-text class="h-5 w-5"/>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="truncate font-medium text-gray-950 dark:text-white">
                                {{ $deleteDocument->original_filename }}
                            </div>

                            <div class="mt-0.5 text-xs text-gray-400">
                                @if ($deleteDocument->size)
                                    {{ $deleteDocument->size >= 1048576
                                        ? number_format($deleteDocument->size / 1048576, 1) . ' MB'
                                        : number_format($deleteDocument->size / 1024, 0) . ' KB' }}
                                @endif

                                @if ($deleteDocument->uploaded_at)
                                    @if ($deleteDocument->size) · @endif
                                    Uploaded {{ $deleteDocument->uploaded_at->timezone('Australia/Hobart')->format('j M Y') }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-danger-50 px-3 py-2.5 text-xs leading-5 text-danger-700 dark:bg-danger-500/10 dark:text-danger-300">
                    This cannot be undone. If you need the file again later, it will need to be uploaded again.
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-white/10">
                    <x-filament::button color="gray" wire:click="cancelDeleteDocument">
                        Cancel
                    </x-filament::button>

                    <x-filament::button
                        color="danger"
                        wire:click="confirmDeleteDocument"
                        wire:loading.attr="disabled"
                        wire:target="confirmDeleteDocument"
                        icon="heroicon-o-trash"
                    >
                        <span wire:loading.remove wire:target="confirmDeleteDocument">Remove document</span>
                        <span wire:loading wire:target="confirmDeleteDocument">Removing…</span>
                    </x-filament::button>
                </div>
            </div>
        @endif
    </x-filament::modal>

    <x-filament::modal id="bookTransactionEditor" width="xl" slide-over>
        <x-slot name="heading">
            {{ $editingTransactionId ? 'Edit Transaction' : 'Add Transaction' }}
            @if ($editingTransactionId)
                <span @class([
                    'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                    'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-300' => $transactionType === 'expense',
                    'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-300' => $transactionType === 'income',])>
                    {{ $transactionType === 'income' ? 'Income' : 'Expense' }}
                </span>
            @endif
        </x-slot>

        <div class="space-y-5">
            {{-- Type --}}
            @if (!$editingTransactionId)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Type</label>
                        <x-books.select model="transactionType" :value="$transactionType" :options="['expense' => 'Expense', 'income' => 'Income']"/>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Date --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Date</label>
                    <x-filament::input.wrapper>
                        <x-filament::input type="date" wire:model="transactionDate"/>
                    </x-filament::input.wrapper>
                    @error('transactionDate')
                    <div class="mt-1 text-xs text-danger-600">{{ $message }}</div> @enderror
                </div>

                {{-- Category --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Category</label>
                    <x-books.select model="editingCategoryId" :value="$editingCategoryId"
                                    :options="$this->editorCategories->whereIn('type', [$transactionType, 'other'])->pluck('name', 'id')->all()" allow-empty empty-label="No category"/>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Amount --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Amount</label>
                    <x-filament::input.wrapper>
                        <x-slot name="prefix">$</x-slot>
                        <x-filament::input type="number" min="0" step="0.01" wire:model.live.debounce.300ms="amountInput"/>
                    </x-filament::input.wrapper>
                    @error('amountInput')
                    <div class="mt-1 text-xs text-danger-600">{{ $message }}</div> @enderror
                </div>

                {{-- GST checkbox --}}
                @if ($this->gstRegistered)
                    <div class="rounded-xl border border-gray-200 p-3 mt-5 dark:border-white/10">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-8">
                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input type="checkbox" wire:model.live="includesGst" @disabled($excludeFromBas) class="rounded border-gray-300 !text-[#e87000] focus:!ring-[#e87000] dark:border-white/20 dark:bg-white/10" style="accent-color: #e87000;">
                                <span class="font-medium">Includes GST</span>
                            </label>
                            {{--}}
                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input type="checkbox" wire:model.live="excludeFromBas" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/20 dark:bg-white/10">
                                <span>
                                <span class="font-medium">Exclude from BAS</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">Rare: the transaction stays in Books but is not included in BAS totals.</span>
                            </span>
                            </label>--}}
                        </div>
                    </div>
                @else
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
                        GST is not split out because this business is not registered for GST.
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Gst --}}
                <div>
                    <div class="mb-1.5 flex items-center justify-between gap-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">GST amount</label>
                        @if ($this->gstRegistered && $includesGst && ! $excludeFromBas)
                            <button type="button" wire:click="recalculateGstAmount" class="text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400">
                                Auto
                            </button>
                        @endif
                    </div>
                    <x-filament::input.wrapper>
                        <x-slot name="prefix">$</x-slot>
                        <x-filament::input type="number" min="0" step="0.01" wire:model.live.debounce.300ms="gstAmountInput" :disabled="! $this->gstRegistered || ! $includesGst || $excludeFromBas"/>
                    </x-filament::input.wrapper>
                    @error('gstAmountInput')
                    <div class="mt-1 text-xs text-danger-600">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Business use --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Business use</label>
                    <x-filament::input.wrapper>
                        <x-slot name="suffix">%</x-slot>
                        <x-filament::input type="number" min="0" max="100" step="1" wire:model.live.debounce.300ms="businessUsePercentage"/>
                    </x-filament::input.wrapper>
                </div>
            </div>

            {{-- Description --}}
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Description</label>
                <textarea wire:model="description" rows="2" class="block w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm
           focus:!border-[#e87000] focus:!outline-none focus:!ring-2 focus:!ring-[#e87000]/40 dark:focus:!border-[#e87000] dark:focus:!ring-[#e87000]/40
           dark:border-white/10 dark:bg-white/5 dark:text-white" placeholder="What was this transaction for?"></textarea>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Capital / Non --}}
                @if ($transactionType === 'expense')
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Purchase type</label>
                        <x-books.select model="purchaseType" :value="$purchaseType" :options="['non_capital' => 'Non-capital', 'capital' => 'Capital']"/>
                    </div>
                @endif

                {{-- Source --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Source</label>
                    <div class="flex min-h-10 items-center rounded-lg bg-gray-50 px-3 text-sm text-gray-700 ring-1 ring-gray-950/10 dark:bg-white/5 dark:text-gray-200 dark:ring-white/10">
                        <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">
                            {{ match ($transactionSource) {
                                'quickbas_migration' => 'QuickBAS',
                                'bank_import' => 'Bank import',
                                'clientbill_invoice' => 'ClientBill invoice',
                                'manual' => 'Manual',
                                default => str($transactionSource)->replace('_', ' ')->title(),
                            } }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Paid from / received into</label>
                    <x-books.select model="paymentSource" :value="$paymentSource" :options="[
                        'Business bank account' => 'Business bank account',
                        'Personal funds' => 'Personal funds',
                        'Cash' => 'Cash',
                        'Other' => 'Other',
                    ]"
                    />
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Notes</label>
                <textarea wire:model="notes" rows="4"
                          class="block w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm focus:!border-[#e87000] focus:!outline-none focus:!ring-2 focus:!ring-[#e87000]/40 dark:focus:!border-[#e87000] dark:focus:!ring-[#e87000]/40 dark:border-white/10 dark:bg-white/5 dark:text-white"></textarea>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 pt-5 dark:border-white/10">
                <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'bookTransactionEditor' })">
                    Cancel
                </x-filament::button>
                <x-filament::button wire:click="saveTransaction" icon="heroicon-o-check">
                    Save Transaction
                </x-filament::button>
            </div>
        </div>
    </x-filament::modal>
</x-filament-panels::page>
