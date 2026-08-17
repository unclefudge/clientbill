<x-filament-panels::page>
    <style id="books-accountant-print-styles">
        .books-accountant-print,
        .books-comparison-print {
            display: none;
        }

        @media print {
            html[data-books-print-mode="pnl"] body *:not(#books-accountant-print):not(#books-accountant-print *):not(:has(#books-accountant-print)),
            html[data-books-print-mode="comparison"] body *:not(#books-comparison-print):not(#books-comparison-print *):not(:has(#books-comparison-print)) {
                display: none !important;
            }

            html[data-books-print-mode="pnl"] #books-accountant-print,
            html[data-books-print-mode="comparison"] #books-comparison-print {
                display: block !important;
                position: static !important;
                width: 100% !important;
                background: #ffffff !important;
                color: #111827 !important;
                font-family: Arial, Helvetica, sans-serif !important;
                font-size: 8.25pt !important;
                line-height: 1.22 !important;
            }

            #books-accountant-print .apr-header {
                display: flex;
                justify-content: space-between;
                gap: 16px;
                padding-bottom: 5px;
                border-bottom: 2px solid #111827;
                margin-bottom: 6px;
            }

            #books-accountant-print .apr-business {
                font-size: 12.5pt;
                font-weight: 700;
            }

            #books-accountant-print .apr-legal,
            #books-accountant-print .apr-meta,
            #books-accountant-print .apr-note {
                color: #4b5563 !important;
                font-size: 8.5pt !important;
            }

            #books-accountant-print .apr-title {
                text-align: right;
                font-size: 15pt;
                font-weight: 700;
            }

            #books-accountant-print .apr-period {
                text-align: right;
                margin-top: 3px;
                font-size: 8.75pt;
                font-weight: 600;
            }

            #books-accountant-print .apr-summary {
                width: 100%;
                border-collapse: collapse;
                margin: 0 0 11px;
            }

            #books-accountant-print .apr-summary td {
                width: 33.333%;
                border: 1px solid #d1d5db;
                padding: 6px 8px;
                vertical-align: top;
            }

            #books-accountant-print .apr-summary-label {
                color: #6b7280 !important;
                font-size: 6.8pt;
                text-transform: uppercase;
                letter-spacing: .04em;
            }

            #books-accountant-print .apr-summary-value {
                margin-top: 3px;
                font-size: 11.5pt;
                font-weight: 700;
                font-variant-numeric: tabular-nums;
            }

            #books-accountant-print .apr-section {
                margin-top: 10px;
                break-inside: avoid;
            }

            #books-accountant-print .apr-heading {
                padding: 3px 0;
                border-bottom: 1.5px solid #111827;
                font-size: 9.25pt;
                font-weight: 700;
            }

            #books-accountant-print table.apr-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 2px;
            }

            #books-accountant-print .apr-table th {
                padding: 3px 4px;
                border-bottom: 1px solid #9ca3af;
                color: #4b5563 !important;
                font-size: 6.8pt;
                font-weight: 700;
                text-align: left;
            }

            #books-accountant-print .apr-table td {
                padding: 3px 4px;
                border-bottom: 1px solid #e5e7eb;
            }

            #books-accountant-print .apr-num {
                text-align: right !important;
                white-space: nowrap;
                font-variant-numeric: tabular-nums;
            }

            #books-accountant-print .apr-total td {
                border-top: 1.5px solid #111827;
                border-bottom: 0;
                font-weight: 700;
                padding-top: 5px;
            }

            #books-accountant-print .apr-profit {
                margin-top: 10px;
                padding: 9px 4px;
                border-top: 2px solid #111827;
                border-bottom: 3px double #111827;
                display: flex;
                justify-content: space-between;
                font-size: 10.25pt;
                font-weight: 700;
            }

            #books-accountant-print .apr-supplementary {
                margin-top: 7px;
                break-before: auto;
            }

            #books-accountant-print .apr-footer {
                margin-top: 7px;
                padding-top: 8px;
                border-top: 1px solid #d1d5db;
                color: #6b7280 !important;
                font-size: 7.8pt !important;
            }

            #books-accountant-print tr {
                break-inside: avoid;
            }

            #books-comparison-print {
                background: #fff !important;
                color: #111827 !important;
                font-family: Arial, Helvetica, sans-serif !important;
                font-size: 6.25pt !important;
                line-height: 1.05 !important;
            }

            #books-comparison-print .cpr-header {
                display: flex;
                justify-content: space-between;
                gap: 16px;
                padding-bottom: 5px;
                border-bottom: 2px solid #111827;
                margin-bottom: 6px;
            }

            #books-comparison-print .cpr-business { font-size: 10.5pt; font-weight: 700; }
            #books-comparison-print .cpr-title { text-align: right; font-size: 12.5pt; font-weight: 700; }
            #books-comparison-print .cpr-period { margin-top: 2px; text-align: right; font-size: 7.25pt; font-weight: 600; }
            #books-comparison-print .cpr-meta,
            #books-comparison-print .cpr-note { color: #4b5563 !important; font-size: 6.1pt !important; }

            #books-comparison-print .cpr-section { margin-top: 6px; }
            #books-comparison-print .cpr-heading {
                padding: 3px 0;
                border-bottom: 1.5px solid #111827;
                font-size: 7.5pt;
                font-weight: 700;
            }

            #books-comparison-print table.cpr-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 2px;
                table-layout: fixed;
            }

            #books-comparison-print .cpr-table thead { display: table-header-group; }

            #books-comparison-print .cpr-table th {
                padding: 2px 4px;
                border-bottom: 1px solid #9ca3af;
                color: #4b5563 !important;
                font-size: 6.1pt;
                font-weight: 700;
                text-align: right;
                vertical-align: bottom;
            }

            #books-comparison-print .cpr-table th:first-child { width: 29%; text-align: left; }

            #books-comparison-print .cpr-table td {
                padding: 2px 4px;
                border-bottom: 1px solid #e5e7eb;
                text-align: right;
                white-space: nowrap;
                font-variant-numeric: tabular-nums;
            }

            #books-comparison-print .cpr-table td:first-child {
                text-align: left;
                white-space: normal;
                font-variant-numeric: normal;
            }

            #books-comparison-print .cpr-current { background: #f3f4f6 !important; }
            #books-comparison-print .cpr-total td { border-top: 1.5px solid #111827; font-weight: 700; }
            #books-comparison-print .cpr-summary td { font-size: 6.8pt; }
            #books-comparison-print .cpr-summary .cpr-profit td {
                border-top: 1.5px solid #111827;
                border-bottom: 3px double #111827;
                font-weight: 700;
            }

            #books-comparison-print .cpr-inactive { color: #6b7280 !important; font-size: 5.6pt; font-weight: 400; }
            #books-comparison-print .cpr-footer {
                margin-top: 7px;
                padding-top: 4px;
                border-top: 1px solid #d1d5db;
                color: #6b7280 !important;
                font-size: 5.8pt !important;
            }

            #books-comparison-print tr { break-inside: avoid; }
        }
    </style>

    <style id="books-runtime-print-page">
        @page { size: A4 portrait; margin: 10mm 11mm 12mm; }
    </style>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Reports</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Profit & Loss calculated from your Books transactions.
            </p>
        </div>

        @if ($this->financialYears->isNotEmpty())
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <x-books.select width="w-full sm:w-40" model="financialYearFilter" :value="$financialYearFilter" :options="$this->financialYearOptions"/>

                <div class="inline-flex h-10 w-fit items-center rounded-lg bg-gray-100 p-1 ring-1 ring-gray-950/10 dark:bg-white/5 dark:ring-white/10">
                    @foreach (['all' => 'All', 'q1' => 'Q1', 'q2' => 'Q2', 'q3' => 'Q3', 'q4' => 'Q4'] as $period => $label)
                        <button type="button" wire:click="$set('periodFilter', '{{ $period }}')"
                            @class([
                                'min-w-11 rounded-md px-3 py-1.5 text-sm font-medium transition',
                                'bg-[#e87000] text-white shadow-sm' => $periodFilter === $period,
                                'text-gray-600 hover:bg-white/70 hover:text-gray-950 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-white' => $periodFilter !== $period,
                            ])>
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="books-no-print flex shrink-0 items-center gap-2 whitespace-nowrap">
                    <button type="button"
                        data-print-mode="{{ $showComparison ? 'comparison' : 'pnl' }}"
                        data-print-title="{{ ($this->getBookBusiness()->legal_name ?: $this->getBookBusiness()->name) . ($showComparison ? ' - P&L Comparison Report' : ' - P&L Report') }}"
                        onclick="
                            const oldTitle = document.title;
                            const root = document.documentElement;
                            const pageStyle = document.getElementById('books-runtime-print-page');
                            root.dataset.booksPrintMode = this.dataset.printMode;
                            document.title = this.dataset.printTitle;
                            pageStyle.textContent = this.dataset.printMode === 'comparison'
                                ? '@page { size: A4 landscape; margin: 6mm 7mm 8mm; }'
                                : '@page { size: A4 portrait; margin: 10mm 11mm 12mm; }';
                            window.addEventListener('afterprint', () => {
                                document.title = oldTitle;
                                delete root.dataset.booksPrintMode;
                            }, { once: true });
                            window.print();
                        "
                        class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg bg-[#e87000] px-3 text-sm font-medium text-white shadow-sm transition hover:bg-[#d86700] focus:outline-none focus:ring-2 focus:ring-[#e87000]/40"
                    >
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
        @endif
    </div>

    @if ($this->financialYears->isEmpty())
        <x-filament::card class="mt-6">
            <div class="py-10 text-center">
                <div class="font-semibold text-gray-950 dark:text-white">No financial year is set up yet</div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add a Books financial year before running reports.</p>
            </div>
        </x-filament::card>
    @else
        @php($report = $this->report)

        <div class="mt-6 flex flex-col gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 sm:flex-row sm:items-center dark:border-white/10 dark:bg-white/[0.03]">
            <div class="min-w-0 flex-1">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Report period</div>
                <div class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $this->periodBounds['label'] }}</div>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Profit & Loss uses business amounts excluding GST.
                    Capital purchases and categories such as loans, distributions and ATO payments are kept outside operating profit.
                </div>
            </div>

            <div class="books-no-print flex w-full shrink-0 flex-nowrap items-center justify-end gap-2 whitespace-nowrap sm:w-[31rem]">
                <button type="button" wire:click="$toggle('showPercentages')"
                    @class([
                        'inline-flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium ring-1 transition',
                        'bg-[#e87000] text-white ring-[#e87000]' => $showPercentages,
                        'text-gray-600 ring-gray-950/10 hover:bg-white hover:text-gray-950 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-white/10 dark:hover:text-white' => ! $showPercentages,
                    ])
                >
                    <x-heroicon-o-chart-bar-square class="h-4 w-4"/>
                    {{ $showPercentages ? 'Hide %' : 'Show %' }}
                </button>

                <button type="button" wire:click="$toggle('showComparison')"
                    @class([
                        'inline-flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium ring-1 transition',
                        'bg-[#e87000] text-white ring-[#e87000]' => $showComparison,
                        'text-gray-600 ring-gray-950/10 hover:bg-white hover:text-gray-950 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-white/10 dark:hover:text-white' => ! $showComparison,
                    ])
                >
                    <x-heroicon-o-arrows-right-left class="h-4 w-4"/>
                    {{ $showComparison ? 'Hide comparison' : 'Compare years' }}
                </button>

                <a href="{{ $this->categoriesUrl }}" wire:navigate class="inline-flex w-fit shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 ring-1 ring-gray-950/10 transition hover:bg-white hover:text-gray-950 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-white/10 dark:hover:text-white">
                    <x-heroicon-o-adjustments-horizontal class="h-4 w-4"/>
                    Review categories
                </a>
            </div>
        </div>

        @unless ($showComparison)
        <div class="mt-5 grid gap-4 md:grid-cols-3">
            <div class="overflow-hidden rounded-xl border border-success-300/40 bg-success-50/60 dark:border-success-500/20 dark:bg-success-500/[0.06]">
                <div class="flex items-center justify-between gap-3 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-success-500/10 text-success-600 dark:text-success-400">
                            <x-heroicon-o-arrow-trending-up class="h-5 w-5"/>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-success-700/70 dark:text-success-300/70">Income</div>
                            <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                {{ count($report['revenue']['groups']) }} {{ \Illuminate\Support\Str::plural('category', count($report['revenue']['groups'])) }}
                            </div>
                        </div>
                    </div>
                    <div class="text-right text-2xl font-bold text-success-600 dark:text-success-400">
                        ${{ number_format($report['revenue']['total'], 2) }}
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-rose-300/40 bg-rose-50/60 dark:border-rose-500/20 dark:bg-rose-500/[0.06]">
                <div class="flex items-center justify-between gap-3 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400">
                            <x-heroicon-o-arrow-trending-down class="h-5 w-5"/>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-rose-700/70 dark:text-rose-300/70">Expenses</div>
                            <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                {{ count($report['operating_expenses']['groups']) }} {{ \Illuminate\Support\Str::plural('category', count($report['operating_expenses']['groups'])) }}
                            </div>
                        </div>
                    </div>
                    <div class="text-right text-2xl font-bold text-gray-950 dark:text-white">
                        ${{ number_format($report['operating_expenses']['total'], 2) }}
                    </div>
                </div>
            </div>

            <div @class([
                'overflow-hidden rounded-xl border',
                'border-sky-300/40 bg-sky-50/60 dark:border-sky-500/20 dark:bg-sky-500/[0.06]' => $report['net_profit'] >= 0,
                'border-danger-300/40 bg-danger-50/60 dark:border-danger-500/20 dark:bg-danger-500/[0.06]' => $report['net_profit'] < 0,
            ])>
                <div class="flex items-center justify-between gap-3 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div @class([
                            'flex h-10 w-10 items-center justify-center rounded-lg',
                            'bg-sky-500/10 text-sky-600 dark:text-sky-400' => $report['net_profit'] >= 0,
                            'bg-danger-500/10 text-danger-600 dark:text-danger-400' => $report['net_profit'] < 0,
                        ])>
                            <x-heroicon-o-chart-bar class="h-5 w-5"/>
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Net profit</div>
                            <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Income less expenses</div>
                        </div>
                    </div>
                    <div @class([
                        'text-right text-2xl font-bold',
                        'text-sky-600 dark:text-sky-400' => $report['net_profit'] >= 0,
                        'text-danger-600 dark:text-danger-400' => $report['net_profit'] < 0,
                    ])>
                        {{ $report['net_profit'] < 0 ? '-' : '' }}${{ number_format(abs($report['net_profit']), 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-5 xl:grid-cols-2">
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-4 border-b border-gray-200 bg-success-50/60 px-5 py-4 dark:border-white/10 dark:bg-success-500/[0.05]">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-success-500/10 text-success-600 dark:text-success-400">
                            <x-heroicon-o-banknotes class="h-5 w-5"/>
                        </div>
                        <div>
                            <h2 class="font-semibold text-gray-950 dark:text-white">Income</h2>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Revenue included in operating profit.</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Total</div>
                        <div class="text-lg font-bold text-success-600 dark:text-success-400">${{ number_format($report['revenue']['total'], 2) }}</div>
                    </div>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($report['revenue']['groups'] as $group)
                        @php($incomeShare = $report['revenue']['total'] != 0 ? abs($group['amount']) / abs($report['revenue']['total']) * 100 : 0)
                        <button type="button" wire:click="openBreakdown('revenue', {{ $group['category_id'] }})"
                            class="grid w-full grid-cols-[8rem_minmax(0,1fr)_10.5rem_1.25rem] items-center gap-3 px-5 py-3 text-sm text-left transition hover:bg-success-50/50 dark:hover:bg-success-500/[0.04]">
                            <div class="whitespace-nowrap pr-6 text-right font-semibold tabular-nums text-gray-950 dark:text-white">
                                {{ $group['amount'] < 0 ? '-' : '' }}${{ number_format(abs($group['amount']), 2) }}
                            </div>
                            <div class="min-w-0">
                                <div class="truncate font-medium text-gray-950 dark:text-white">{{ $group['name'] }}</div>
                                @if ($showPercentages)
                                    <div class="mt-1 h-1.5 max-w-40 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                        <div class="h-full rounded-full bg-success-500/70" style="width: {{ min(100, $incomeShare) }}%"></div>
                                    </div>
                                @endif
                            </div>
                            <div class="grid grid-cols-[3rem_minmax(0,1fr)] items-center gap-2 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                @if ($showPercentages)
                                    <span class="text-right font-semibold tabular-nums text-gray-900 dark:text-white">{{ number_format($incomeShare, 0) }}%</span>
                                @else
                                    <span></span>
                                @endif
                                <span class="text-left">
                                    {{ $group['count'] }} {{ \Illuminate\Support\Str::plural('transaction', $group['count']) }}
                                </span>
                            </div>
                            <x-heroicon-o-chevron-right class="h-4 w-4 text-gray-400"/>
                        </button>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No income transactions in this period.</div>
                    @endforelse
                </div>

                <div class="grid grid-cols-[8rem_minmax(0,1fr)_10.5rem_1.25rem] items-center gap-3 border-t border-gray-200 bg-gray-50 px-5 py-3 font-semibold dark:border-white/10 dark:bg-white/[0.03]">
                    <span class="pr-6 text-right tabular-nums text-gray-950 dark:text-white">${{ number_format($report['revenue']['total'], 2) }}</span>
                    <span class="text-gray-950 dark:text-white">Total income</span>
                    <span></span>
                    <span></span>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-4 border-b border-gray-200 bg-rose-50/60 px-5 py-4 dark:border-white/10 dark:bg-rose-500/[0.05]">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400">
                            <x-heroicon-o-receipt-percent class="h-5 w-5"/>
                        </div>
                        <div>
                            <h2 class="font-semibold text-gray-950 dark:text-white">Operating expenses</h2>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Day-to-day costs included in operating profit.</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Total</div>
                        <div class="text-lg font-bold text-gray-950 dark:text-white">${{ number_format($report['operating_expenses']['total'], 2) }}</div>
                    </div>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($report['operating_expenses']['groups'] as $group)
                        @php($expenseShare = $report['operating_expenses']['total'] != 0 ? abs($group['amount']) / abs($report['operating_expenses']['total']) * 100 : 0)
                        <button type="button" wire:click="openBreakdown('operating_expense', {{ $group['category_id'] }})"
                            class="grid w-full grid-cols-[8rem_minmax(0,1fr)_10.5rem_1.25rem] items-center gap-3 px-5 py-3 text-sm text-left transition hover:bg-rose-50/50 dark:hover:bg-rose-500/[0.04]">
                            <div class="whitespace-nowrap pr-6 text-right font-semibold tabular-nums text-gray-950 dark:text-white">
                                {{ $group['amount'] < 0 ? '-' : '' }}${{ number_format(abs($group['amount']), 2) }}
                            </div>
                            <div class="min-w-0">
                                <div class="truncate font-medium text-gray-950 dark:text-white">{{ $group['name'] }}</div>
                                @if ($showPercentages)
                                    <div class="mt-1 h-1.5 max-w-40 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                        <div class="h-full rounded-full bg-rose-500/70" style="width: {{ min(100, $expenseShare) }}%"></div>
                                    </div>
                                @endif
                            </div>
                            <div class="grid grid-cols-[3rem_minmax(0,1fr)] items-center gap-2 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                @if ($showPercentages)
                                    <span class="text-right font-semibold tabular-nums text-gray-900 dark:text-white">{{ number_format($expenseShare, 0) }}%</span>
                                @else
                                    <span></span>
                                @endif
                                <span class="text-left">
                                    {{ $group['count'] }} {{ \Illuminate\Support\Str::plural('transaction', $group['count']) }}
                                </span>
                            </div>
                            <x-heroicon-o-chevron-right class="h-4 w-4 text-gray-400"/>
                        </button>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No operating expenses in this period.</div>
                    @endforelse
                </div>

                <div class="grid grid-cols-[8rem_minmax(0,1fr)_10.5rem_1.25rem] items-center gap-3 border-t border-gray-200 bg-gray-50 px-5 py-3 font-semibold dark:border-white/10 dark:bg-white/[0.03]">
                    <span class="pr-6 text-right tabular-nums text-gray-950 dark:text-white">${{ number_format($report['operating_expenses']['total'], 2) }}</span>
                    <span class="text-gray-950 dark:text-white">Total expenses</span>
                    <span></span>
                    <span></span>
                </div>
            </section>
        </div>


        @endunless

        @if ($showComparison)
            @php($comparison = $this->comparison)

            <section class="books-print-break mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                <div class="border-b border-gray-200 bg-gray-50 px-5 py-4 dark:border-white/10 dark:bg-white/[0.03]">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="font-semibold text-gray-950 dark:text-white">Year comparison</h2>
                            <p class="mt-1 max-w-3xl text-xs text-gray-500 dark:text-gray-400">
                                {{ $this->comparisonDescription }}
                            </p>
                        </div>

                        <div class="text-xs font-medium text-gray-400">
                            Up to 5 financial years
                        </div>
                    </div>
                </div>

                @if ($comparison['years'] === [])
                    <div class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                        No previous financial years are available to compare.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px] text-sm">
                            <thead class="bg-gray-50/70 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th class="sticky left-0 z-[1] min-w-52 bg-gray-50 px-5 py-3 text-left dark:bg-gray-900">Summary</th>
                                    @foreach ($comparison['years'] as $year)
                                        <th @class([
                                            'min-w-28 px-4 py-3 text-right',
                                            'bg-orange-50 text-[#e87000] dark:bg-[#e87000]/10' => $year['selected'],
                                        ])>
                                            <div>{{ $year['label'] }}</div>
                                            <div @class([
                                                'mt-0.5 text-[10px] font-normal normal-case',
                                                'text-[#e87000] dark:text-orange-300' => $year['partial'],
                                                'text-gray-400' => ! $year['partial'],
                                            ])>
                                                {{ $year['period_note'] }}
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                <tr>
                                    <td class="sticky left-0 bg-white px-5 py-3 font-medium text-gray-950 dark:bg-gray-900 dark:text-white">Income</td>
                                    @foreach ($comparison['years'] as $year)
                                        <td @class([
                                            'px-4 py-3 text-right font-semibold tabular-nums',
                                            'bg-orange-50/60 text-gray-950 dark:bg-[#e87000]/5 dark:text-white' => $year['selected'],
                                            'text-gray-700 dark:text-gray-300' => ! $year['selected'],
                                        ])>
                                            ${{ number_format($year['revenue_total'], 2) }}
                                        </td>
                                    @endforeach
                                </tr>
                                <tr>
                                    <td class="sticky left-0 bg-white px-5 py-3 font-medium text-gray-950 dark:bg-gray-900 dark:text-white">Expenses</td>
                                    @foreach ($comparison['years'] as $year)
                                        <td @class([
                                            'px-4 py-3 text-right font-semibold tabular-nums',
                                            'bg-orange-50/60 text-gray-950 dark:bg-[#e87000]/5 dark:text-white' => $year['selected'],
                                            'text-gray-700 dark:text-gray-300' => ! $year['selected'],
                                        ])>
                                            ${{ number_format($year['expense_total'], 2) }}
                                        </td>
                                    @endforeach
                                </tr>
                                <tr class="bg-gray-50/70 dark:bg-white/[0.02]">
                                    <td class="sticky left-0 bg-gray-50 px-5 py-3 font-bold text-gray-950 dark:bg-gray-900 dark:text-white">Net profit</td>
                                    @foreach ($comparison['years'] as $year)
                                        <td @class([
                                            'px-4 py-3 text-right font-bold tabular-nums',
                                            'bg-orange-50 text-[#e87000] dark:bg-[#e87000]/10' => $year['selected'],
                                            'text-gray-950 dark:text-white' => ! $year['selected'],
                                        ])>
                                            {{ $year['net_profit'] < 0 ? '-' : '' }}${{ number_format(abs($year['net_profit']), 2) }}
                                        </td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-5 border-t border-gray-200 dark:border-white/10">
                        <div class="bg-gray-50/60 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-success-600 dark:bg-white/[0.025] dark:text-success-400">Income by category</div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[760px] text-sm">
                                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                    @foreach ($comparison['revenue'] as $row)
                                        <tr class="hover:bg-success-50/30 dark:hover:bg-success-500/[0.03]">
                                            <td class="sticky left-0 min-w-52 bg-white px-5 py-2.5 font-medium text-gray-950 dark:bg-gray-900 dark:text-white">
                                                <span>{{ $row['name'] }}</span>
                                                @if ($row['active'] === false)
                                                    <span class="ml-1.5 inline-flex rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-500 dark:bg-white/10 dark:text-gray-400">Inactive</span>
                                                @endif
                                            </td>
                                            @foreach ($comparison['years'] as $year)
                                                @php($value = $row['values'][$year['key']] ?? null)
                                                <td @class([
                                                    'min-w-28 px-4 py-2.5 text-right tabular-nums',
                                                    'bg-orange-50/40 dark:bg-[#e87000]/5' => $year['selected'],
                                                    'text-gray-700 dark:text-gray-300',
                                                ])>
                                                    {{ $value === null ? '—' : '$' . number_format($value, 2) }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-5 border-t border-gray-200 dark:border-white/10">
                        <div class="bg-gray-50/60 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-rose-600 dark:bg-white/[0.025] dark:text-rose-400">Expenses by category</div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[760px] text-sm">
                                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                    @foreach ($comparison['expenses'] as $row)
                                        <tr class="hover:bg-rose-50/30 dark:hover:bg-rose-500/[0.03]">
                                            <td class="sticky left-0 min-w-52 bg-white px-5 py-2.5 font-medium text-gray-950 dark:bg-gray-900 dark:text-white">
                                                <span>{{ $row['name'] }}</span>
                                                @if ($row['active'] === false)
                                                    <span class="ml-1.5 inline-flex rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-500 dark:bg-white/10 dark:text-gray-400">Inactive</span>
                                                @endif
                                            </td>
                                            @foreach ($comparison['years'] as $year)
                                                @php($value = $row['values'][$year['key']] ?? null)
                                                <td @class([
                                                    'min-w-28 px-4 py-2.5 text-right tabular-nums',
                                                    'bg-orange-50/40 dark:bg-[#e87000]/5' => $year['selected'],
                                                    'text-gray-700 dark:text-gray-300',
                                                ])>
                                                    {{ $value === null ? '—' : '$' . number_format($value, 2) }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </section>
        @endif

        @unless ($showComparison)
        @if ($report['outside_pnl'] !== [])
            <div class="mt-6" x-data="{ open: false }">
                <button type="button" x-on:click="open = ! open"
                    class="flex w-full items-center justify-between gap-4 rounded-xl border border-gray-200 bg-gray-50 px-5 py-4 text-left transition hover:bg-gray-100 dark:border-white/10 dark:bg-white/[0.03] dark:hover:bg-white/[0.05]">
                    <div>
                        <div class="font-semibold text-gray-950 dark:text-white">Not included in Profit & Loss</div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $report['outside_transaction_count'] }} {{ \Illuminate\Support\Str::plural('transaction', $report['outside_transaction_count']) }}
                            kept outside operating profit.
                        </div>
                    </div>
                    <x-heroicon-o-chevron-down class="h-5 w-5 text-gray-400 transition" x-bind:class="{ 'rotate-180': open }"/>
                </button>

                <div x-cloak x-show="open" x-transition.opacity class="mt-3 space-y-3">
                    @foreach ($report['outside_pnl'] as $section)
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                            <div class="flex items-start justify-between gap-4 border-b border-gray-200 bg-gray-50 px-5 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                                <div>
                                    <div class="font-semibold text-gray-950 dark:text-white">{{ $section['label'] }}</div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $section['description'] }}</div>
                                </div>
                                <div class="whitespace-nowrap text-right text-sm font-semibold text-gray-700 dark:text-gray-200">
                                    Net movement
                                    {{ $section['net_movement'] < 0 ? '-' : '+' }}${{ number_format(abs($section['net_movement']), 2) }}
                                </div>
                            </div>

                            <div class="divide-y divide-gray-100 dark:divide-white/5">
                                @foreach ($section['groups'] as $group)
                                    <button type="button" wire:click="openBreakdown('{{ $section['treatment'] }}', {{ $group['category_id'] }})"
                                        class="grid w-full grid-cols-[minmax(0,1fr)_8rem_1.5rem] items-center gap-3 px-5 py-3 text-left transition hover:bg-gray-50 dark:hover:bg-white/[0.03]">
                                        <div>
                                            <div class="font-medium text-gray-950 dark:text-white">{{ $group['name'] }}</div>
                                            <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                                {{ $group['count'] }} {{ \Illuminate\Support\Str::plural('transaction', $group['count']) }}
                                            </div>
                                        </div>
                                        <div class="text-right font-medium text-gray-700 dark:text-gray-200">
                                            {{ $group['amount'] < 0 ? '-' : '+' }}${{ number_format(abs($group['amount']), 2) }}
                                        </div>
                                        <x-heroicon-o-chevron-right class="h-4 w-4 text-gray-400"/>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-5 text-xs text-gray-400">
            {{ $report['pnl_transaction_count'] }} of {{ $report['transaction_count'] }}
            {{ \Illuminate\Support\Str::plural('transaction', $report['transaction_count']) }}
            contribute directly to operating Profit & Loss for this period.
        </div>
        @endunless

        {{-- Dedicated white-paper Profit & Loss for accountant / PDF printing --}}
        @php($printBusiness = $this->getBookBusiness())
        <section id="books-accountant-print" class="books-accountant-print">
            <div class="apr-header">
                <div>
                    <div class="apr-business">{{ $printBusiness->legal_name ?: $printBusiness->name }}</div>
                    @if ($printBusiness->legal_name && $printBusiness->legal_name !== $printBusiness->name)
                        <div class="apr-legal">Trading as {{ $printBusiness->name }}</div>
                    @endif
                    @if ($printBusiness->abn)
                        <div class="apr-meta">ABN {{ $printBusiness->abn }}</div>
                    @endif
                </div>

                <div>
                    <div class="apr-title">Profit &amp; Loss</div>
                    <div class="apr-period">{{ $this->periodBounds['label'] }}</div>
                    <div class="apr-meta" style="text-align:right; margin-top:3px;">
                        Generated {{ now('Australia/Hobart')->format('j M Y') }}
                    </div>
                </div>
            </div>

            <table class="apr-summary">
                <tr>
                    <td>
                        <div class="apr-summary-label">Total income</div>
                        <div class="apr-summary-value">${{ number_format($report['revenue']['total'], 2) }}</div>
                    </td>
                    <td>
                        <div class="apr-summary-label">Operating expenses</div>
                        <div class="apr-summary-value">${{ number_format($report['operating_expenses']['total'], 2) }}</div>
                    </td>
                    <td>
                        <div class="apr-summary-label">Net profit</div>
                        <div class="apr-summary-value">
                            {{ $report['net_profit'] < 0 ? '-' : '' }}${{ number_format(abs($report['net_profit']), 2) }}
                        </div>
                    </td>
                </tr>
            </table>

            <div class="apr-note">
                Amounts are business-use amounts excluding GST. Capital/asset, loan/finance,
                distribution/equity and Tax/ATO movements are excluded from operating Profit &amp; Loss.
            </div>

            <div class="apr-section">
                <div class="apr-heading">Income</div>
                <table class="apr-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th class="apr-num">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['revenue']['groups'] as $group)
                            <tr>
                                <td>{{ $group['name'] }}</td>
                                <td class="apr-num">{{ $group['amount'] < 0 ? '-' : '' }}${{ number_format(abs($group['amount']), 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="apr-total">
                            <td>Total income</td>
                            <td class="apr-num">${{ number_format($report['revenue']['total'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="apr-section">
                <div class="apr-heading">Operating expenses</div>
                <table class="apr-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th class="apr-num">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['operating_expenses']['groups'] as $group)
                            <tr>
                                <td>{{ $group['name'] }}</td>
                                <td class="apr-num">{{ $group['amount'] < 0 ? '-' : '' }}${{ number_format(abs($group['amount']), 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="apr-total">
                            <td>Total operating expenses</td>
                            <td class="apr-num">${{ number_format($report['operating_expenses']['total'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="apr-profit">
                <span>Net profit</span>
                <span>{{ $report['net_profit'] < 0 ? '-' : '' }}${{ number_format(abs($report['net_profit']), 2) }}</span>
            </div>

            @if ($report['outside_pnl'] !== [])
                <div class="apr-supplementary">
                    <div class="apr-heading">Supplementary movements — not included in Profit &amp; Loss</div>
                    <div class="apr-note" style="margin-top:4px;">
                        Included for accountant reference; these movements do not form part of operating net profit above.
                    </div>

                    @foreach ($report['outside_pnl'] as $section)
                        <table class="apr-table" style="margin-top:10px;">
                            <thead>
                                <tr>
                                    <th>{{ $section['label'] }}</th>
                                    <th class="apr-num">Net movement</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($section['groups'] as $group)
                                    <tr>
                                        <td>{{ $group['name'] }}</td>
                                        <td class="apr-num">{{ $group['amount'] < 0 ? '-' : '+' }}${{ number_format(abs($group['amount']), 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="apr-total">
                                    <td>{{ $section['label'] }} total</td>
                                    <td class="apr-num">{{ $section['net_movement'] < 0 ? '-' : '+' }}${{ number_format(abs($section['net_movement']), 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    @endforeach
                </div>
            @endif

            <div class="apr-footer">
                Prepared from ClientBill Books transaction records for {{ $printBusiness->legal_name ?: $printBusiness->name }}.
                Profit &amp; Loss amounts are based on the selected reporting period and category treatments shown in Books.
            </div>
        </section>

        {{-- Comparison Print --}}
        @if ($showComparison)
            @php($printComparison = $this->comparison)
            <section id="books-comparison-print" class="books-comparison-print">
                <div class="cpr-header">
                    <div>
                        <div class="cpr-business">{{ $printBusiness->legal_name ?: $printBusiness->name }}</div>
                        @if ($printBusiness->legal_name && $printBusiness->legal_name !== $printBusiness->name)
                            <div class="cpr-meta">Trading as {{ $printBusiness->name }}</div>
                        @endif
                        @if ($printBusiness->abn)
                            <div class="cpr-meta">ABN {{ $printBusiness->abn }}</div>
                        @endif
                    </div>

                    <div>
                        <div class="cpr-title">Profit &amp; Loss Comparison</div>
                        <div class="cpr-period">{{ $periodFilter === 'all' ? 'Financial year comparison' : strtoupper($periodFilter) . ' comparison' }}</div>
                        <div class="cpr-meta" style="text-align:right; margin-top:3px;">Generated {{ now('Australia/Hobart')->format('j M Y') }}</div>
                    </div>
                </div>

                <div class="cpr-note">
                    {{ $this->comparisonDescription }} Amounts are business-use amounts excluding GST.
                </div>

                @if ($printComparison['years'] !== [])
                    <div class="cpr-section">
                        <div class="cpr-heading">Summary</div>
                        <table class="cpr-table cpr-summary">
                            <thead>
                                <tr>
                                    <th>Result</th>
                                    @foreach ($printComparison['years'] as $year)
                                        <th class="{{ $year['selected'] ? 'cpr-current' : '' }}">
                                            {{ $year['label'] }}
                                            <div style="margin-top:2px; font-size:5.7pt; font-weight:400;">{{ $year['period_note'] }}</div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Income</td>
                                    @foreach ($printComparison['years'] as $year)
                                        <td class="{{ $year['selected'] ? 'cpr-current' : '' }}">${{ number_format($year['revenue_total'], 2) }}</td>
                                    @endforeach
                                </tr>
                                <tr>
                                    <td>Operating expenses</td>
                                    @foreach ($printComparison['years'] as $year)
                                        <td class="{{ $year['selected'] ? 'cpr-current' : '' }}">${{ number_format($year['expense_total'], 2) }}</td>
                                    @endforeach
                                </tr>
                                <tr class="cpr-profit">
                                    <td>Net profit</td>
                                    @foreach ($printComparison['years'] as $year)
                                        <td class="{{ $year['selected'] ? 'cpr-current' : '' }}">{{ $year['net_profit'] < 0 ? '-' : '' }}${{ number_format(abs($year['net_profit']), 2) }}</td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="cpr-section">
                        <div class="cpr-heading">Income by category</div>
                        <table class="cpr-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    @foreach ($printComparison['years'] as $year)
                                        <th class="{{ $year['selected'] ? 'cpr-current' : '' }}">{{ $year['label'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($printComparison['revenue'] as $row)
                                    <tr>
                                        <td>
                                            {{ $row['name'] }}
                                            @if ($row['active'] === false)<span class="cpr-inactive"> (inactive)</span>@endif
                                        </td>
                                        @foreach ($printComparison['years'] as $year)
                                            @php($value = $row['values'][$year['key']] ?? null)
                                            <td class="{{ $year['selected'] ? 'cpr-current' : '' }}">{{ $value === null ? '—' : '$' . number_format($value, 2) }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                <tr class="cpr-total">
                                    <td>Total income</td>
                                    @foreach ($printComparison['years'] as $year)
                                        <td class="{{ $year['selected'] ? 'cpr-current' : '' }}">${{ number_format($year['revenue_total'], 2) }}</td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="cpr-section">
                        <div class="cpr-heading">Operating expenses by category</div>
                        <table class="cpr-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    @foreach ($printComparison['years'] as $year)
                                        <th class="{{ $year['selected'] ? 'cpr-current' : '' }}">{{ $year['label'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($printComparison['expenses'] as $row)
                                    <tr>
                                        <td>
                                            {{ $row['name'] }}
                                            @if ($row['active'] === false)<span class="cpr-inactive"> (inactive)</span>@endif
                                        </td>
                                        @foreach ($printComparison['years'] as $year)
                                            @php($value = $row['values'][$year['key']] ?? null)
                                            <td class="{{ $year['selected'] ? 'cpr-current' : '' }}">{{ $value === null ? '—' : '$' . number_format($value, 2) }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                <tr class="cpr-total">
                                    <td>Total operating expenses</td>
                                    @foreach ($printComparison['years'] as $year)
                                        <td class="{{ $year['selected'] ? 'cpr-current' : '' }}">${{ number_format($year['expense_total'], 2) }}</td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="cpr-footer">
                        Prior financial years show their complete selected period. If the selected current period is still underway, its column is shown only to the date stated in the heading. Inactive categories remain visible where they contain historical transactions.
                    </div>
                @endif
            </section>
        @endif
    @endif

    <x-filament::modal id="bookReportBreakdown" width="5xl" slide-over>
        @if ($breakdownTreatment && $breakdownCategoryId !== null)
            <x-slot name="heading">
                {{ $this->breakdownTitle }}
            </x-slot>

            <x-slot name="description">
                {{ $this->breakdownTreatmentLabel }} · {{ $this->periodBounds['label'] }}
            </x-slot>

            <div class="mb-4 flex items-center justify-between gap-4 rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Transactions</div>
                    <div class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ $this->breakdownTransactions->count() }}
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                        {{ in_array($breakdownTreatment, ['revenue', 'operating_expense'], true) ? 'P&L contribution' : 'Net movement' }}
                    </div>
                    <div class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">
                        {{ $this->breakdownTotal < 0 ? '-' : '' }}${{ number_format(abs($this->breakdownTotal), 2) }}
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                <div class="max-h-[65vh] overflow-auto">
                    <table class="w-full min-w-[760px] text-sm">
                        <thead class="sticky top-0 z-10 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Description</th>
                                <th class="px-4 py-3 text-right">Business amount</th>
                                <th class="px-4 py-3 text-right">GST</th>
                                <th class="px-4 py-3 text-right">Net / contribution</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse ($this->breakdownTransactions as $transaction)
                                @php($contribution = $this->breakdownContribution($transaction))
                                <tr class="hover:bg-gray-50/80 dark:hover:bg-white/[0.03]">
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">
                                        {{ $transaction->transaction_date->format('j M Y') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-950 dark:text-white">
                                            {{ $transaction->description ?: 'No description' }}
                                        </div>
                                        @if ((float) $transaction->business_use_percentage !== 100.0)
                                            <div class="mt-0.5 text-xs text-gray-400">
                                                {{ number_format((float) $transaction->business_use_percentage, 0) }}% business use
                                            </div>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-gray-600 dark:text-gray-300">
                                        {{ (float) $transaction->business_amount < 0 ? '-' : '+' }}${{ number_format(abs((float) $transaction->business_amount), 2) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-gray-500 dark:text-gray-400">
                                        {{ (float) $transaction->gst_amount > 0 ? '$' . number_format((float) $transaction->gst_amount, 2) : '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-gray-950 dark:text-white">
                                        {{ $contribution < 0 ? '-' : '+' }}${{ number_format(abs($contribution), 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                        No matching transactions.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </x-filament::modal>


</x-filament-panels::page>
