<x-filament-panels::page>
    <style>
        .books-bas-print {
            display: none;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm 14mm 14mm;
            }

            body *:not(#books-bas-print):not(#books-bas-print *):not(:has(#books-bas-print)) {
                display: none !important;
            }

            #books-bas-print {
                display: block !important;
                position: static !important;
                width: 100% !important;
                background: #fff !important;
                color: #111827 !important;
                font-family: Arial, Helvetica, sans-serif !important;
                font-size: 9pt !important;
                line-height: 1.25 !important;
            }

            #books-bas-print .bpr-header {
                display: flex;
                justify-content: space-between;
                gap: 24px;
                padding-bottom: 9px;
                border-bottom: 2px solid #111827;
                margin-bottom: 12px;
            }

            #books-bas-print .bpr-business {
                font-size: 13pt;
                font-weight: 700;
            }

            #books-bas-print .bpr-title {
                text-align: right;
                font-size: 16pt;
                font-weight: 700;
            }

            #books-bas-print .bpr-period {
                margin-top: 3px;
                text-align: right;
                font-size: 9pt;
                font-weight: 600;
            }

            #books-bas-print .bpr-meta,
            #books-bas-print .bpr-note {
                color: #4b5563 !important;
                font-size: 8pt !important;
            }

            #books-bas-print .bpr-status {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr;
                border: 1px solid #d1d5db;
                margin-bottom: 12px;
            }

            #books-bas-print .bpr-status > div {
                padding: 7px 9px;
                border-right: 1px solid #d1d5db;
            }

            #books-bas-print .bpr-status > div:last-child {
                border-right: 0;
            }

            #books-bas-print .bpr-label {
                color: #6b7280 !important;
                font-size: 7pt;
                text-transform: uppercase;
                letter-spacing: .04em;
            }

            #books-bas-print .bpr-value {
                margin-top: 2px;
                font-size: 9.5pt;
                font-weight: 700;
            }

            #books-bas-print .bpr-section {
                margin-top: 14px;
            }

            #books-bas-print .bpr-heading {
                padding-bottom: 5px;
                border-bottom: 1.5px solid #111827;
                font-size: 10pt;
                font-weight: 700;
            }

            #books-bas-print .bpr-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 4px;
            }

            #books-bas-print .bpr-table td {
                padding: 7px 5px;
                border-bottom: 1px solid #e5e7eb;
            }

            #books-bas-print .bpr-code {
                width: 48px;
                font-weight: 700;
            }

            #books-bas-print .bpr-desc {
                color: #374151 !important;
            }

            #books-bas-print .bpr-whole,
            #books-bas-print .bpr-exact {
                text-align: right;
                white-space: nowrap;
                font-variant-numeric: tabular-nums;
            }

            #books-bas-print .bpr-whole {
                width: 105px;
                font-weight: 700;
            }

            #books-bas-print .bpr-exact {
                width: 115px;
                color: #6b7280 !important;
                font-size: 8pt;
            }

            #books-bas-print .bpr-net td {
                border-top: 2px solid #111827;
                border-bottom: 3px double #111827;
                font-size: 10pt;
                font-weight: 700;
            }

            #books-bas-print .bpr-footer {
                margin-top: 16px;
                padding-top: 8px;
                border-top: 1px solid #d1d5db;
                color: #6b7280 !important;
                font-size: 7.5pt !important;
            }
        }
    </style>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">BAS</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Live GST figures calculated from Books transactions.</p>
        </div>

        @if ($this->financialYears->isNotEmpty())
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <x-books.select width="w-full sm:w-40" model="financialYearFilter" :value="$financialYearFilter" :options="$this->financialYearOptions"/>

                <div class="inline-flex h-10 w-fit items-center rounded-lg bg-gray-100 p-1 ring-1 ring-gray-950/10 dark:bg-white/5 dark:ring-white/10">
                    @foreach (['q1' => 'Jul-Sep', 'q2' => 'Oct-Dec', 'q3' => 'Jan-Mar', 'q4' => 'Apr-Jun', 'annual' => 'Annual'] as $period => $label)
                        <button type="button" wire:click="$set('periodFilter', '{{ $period }}')"
                            @class([
                                'rounded-md px-3 py-1.5 text-sm font-medium transition',
                                'bg-[#e87000] text-white shadow-sm' => $periodFilter === $period,
                                'text-gray-600 hover:bg-white/70 hover:text-gray-950 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-white' => $periodFilter !== $period,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <button type="button" data-print-title="{{ ($this->getBookBusiness()->legal_name ?: $this->getBookBusiness()->name) . ' - BAS - ' . $this->periodBounds['label'] }}"
                    onclick="const oldTitle = document.title; document.title = this.dataset.printTitle; window.addEventListener('afterprint', () => document.title = oldTitle, { once: true }); window.print();"
                    class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg bg-[#e87000] px-3 text-sm font-medium text-white shadow-sm transition hover:bg-[#d86700] focus:outline-none focus:ring-2 focus:ring-[#e87000]/40">
                    <x-heroicon-o-printer class="h-4 w-4"/>
                    Print
                </button>
            </div>
        @endif
    </div>

    @if ($this->financialYears->isEmpty())
        <x-filament::card class="mt-6">
            <div class="py-10 text-center">
                <div class="text-base font-semibold text-gray-950 dark:text-white">No financial year is set up yet</div>
                <p class="mx-auto mt-2 max-w-xl text-sm text-gray-500 dark:text-gray-400">Add a Books financial year before calculating BAS periods.</p>
            </div>
        </x-filament::card>
    @else
        @php
            $bas = $this->bas;
            $bounds = $this->periodBounds;
            $netPayable = (float) $bas['net_amount'] >= 0;
        @endphp

        @if (! $this->gstRegistered)
            <div class="mt-6 rounded-xl border border-warning-300 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-200">
                This business is not marked as GST registered. The period figures below are bookkeeping summaries only.
            </div>
        @endif

        <div class="mt-6">
            <x-filament::card>
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Selected period</div>
                        <div class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">{{ $bounds['label'] }}</div>
                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $bas['transaction_count'] }} included
                            @if ($bas['excluded_count'])
                                · {{ $bas['excluded_count'] }} excluded from BAS
                            @endif
                            @if ($bas['uncategorised_count'])
                                · <span class="font-medium text-warning-600 dark:text-warning-400">{{ $bas['uncategorised_count'] }} uncategorised</span>
                            @endif
                        </div>

                        @if ($this->savedBasPeriod?->status === 'lodged' && $this->savedBasPeriod->lodged_at)
                            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Lodged {{ $this->savedBasPeriod->lodged_at->timezone('Australia/Hobart')->format('j M Y') }}
                                · snapshot locked
                            </div>
                        @elseif ($this->savedBasPeriod?->status === 'reopened' && $this->savedBasPeriod->lodged_at)
                            <div class="mt-2 text-xs font-medium text-warning-600 dark:text-warning-400">
                                Reopened after lodgement on {{ $this->savedBasPeriod->lodged_at->timezone('Australia/Hobart')->format('j M Y') }}
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-col items-start gap-2 lg:items-end">
                        @if ($periodFilter !== 'annual' && $this->selectedQuarterStatus)
                            @php($quarterStatus = $this->selectedQuarterStatus)

                            <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                                <span @class([
                                    'inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-300' => in_array($quarterStatus['status'], ['lodged', 'historical'], true),
                                    'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-300' => $quarterStatus['status'] === 'overdue',
                                    'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-300' => in_array($quarterStatus['status'], ['due_soon', 'reopened'], true),
                                    'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300' => in_array($quarterStatus['status'], ['open', 'ready'], true),
                                    'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400' => $quarterStatus['status'] === 'future',
                                ])>
                                    {{ match ($quarterStatus['status']) {
                                        'due_soon' => 'Due soon',
                                        'ready' => 'Ready',
                                        default => ucfirst($quarterStatus['status']),
                                    } }}
                                </span>

                                @if ($quarterStatus['status'] === 'lodged' && $quarterStatus['whole_net'] !== null)
                                    <span class="text-sm font-semibold {{ $quarterStatus['whole_net'] >= 0 ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400' }}">
                                        {{ $quarterStatus['whole_net'] >= 0 ? 'Payable' : 'Refund' }}
                                        ${{ number_format(abs($quarterStatus['whole_net'])) }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Due {{ $quarterStatus['due_date']->format('j M Y') }}
                                    </span>
                                @elseif ($quarterStatus['status'] === 'overdue')
                                    <span class="text-xs font-semibold text-danger-600 dark:text-danger-400">
                                        {{ $quarterStatus['days_overdue'] }} {{ \Illuminate\Support\Str::plural('day', $quarterStatus['days_overdue']) }} overdue
                                        · was due {{ $quarterStatus['due_date']->format('j M Y') }}
                                    </span>
                                @elseif ($quarterStatus['status'] === 'due_soon')
                                    <span class="text-xs font-semibold text-warning-600 dark:text-warning-400">
                                        Due in {{ max(0, $quarterStatus['days_until_due']) }} {{ \Illuminate\Support\Str::plural('day', max(0, $quarterStatus['days_until_due'])) }}
                                        · {{ $quarterStatus['due_date']->format('j M Y') }}
                                    </span>
                                @elseif ($quarterStatus['status'] === 'ready')
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Ready to lodge · due {{ $quarterStatus['due_date']->format('j M Y') }}
                                    </span>
                                @elseif ($quarterStatus['status'] === 'reopened')
                                    <span class="text-xs font-medium text-warning-600 dark:text-warning-400">Editing unlocked</span>
                                @elseif ($quarterStatus['status'] === 'open')
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Standard ATO due {{ $quarterStatus['due_date']->format('j M Y') }}
                                    </span>
                                @elseif ($quarterStatus['status'] === 'future')
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Starts {{ $quarterStatus['start']->format('j M Y') }}
                                    </span>
                                @elseif ($quarterStatus['status'] === 'historical')
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Closed financial year</span>
                                @endif
                            </div>

                            @if ($this->selectedFinancialYear?->status !== 'closed')
                                @if ($this->savedBasPeriod?->status === 'lodged')
                                    <x-filament::button color="gray" wire:click="openReopenConfirmation" icon="heroicon-o-lock-open">
                                        Reopen BAS
                                    </x-filament::button>
                                @elseif ($this->canLodgeSelectedPeriod)
                                    <x-filament::button wire:click="openLodgeConfirmation" icon="heroicon-o-lock-closed">
                                        {{ $this->lodgeButtonLabel }}
                                    </x-filament::button>
                                @elseif ($bounds['end']->copy()->endOfDay()->isFuture() && $quarterStatus['status'] === 'open')
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Lodgement available after {{ $bounds['end']->format('j M Y') }}
                                    </span>
                                @endif
                            @endif
                        @elseif ($this->savedBasPeriod)
                            <span @class([
                                'inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-semibold',
                                'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-300' => in_array($this->savedBasPeriod->status, ['historical', 'lodged'], true),
                                'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-300' => $this->savedBasPeriod->status === 'reopened',
                                'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300' => ! in_array($this->savedBasPeriod->status, ['historical', 'lodged', 'reopened'], true),
                            ])>
                                {{ ucfirst($this->savedBasPeriod->status) }} snapshot
                            </span>
                        @endif
                    </div>
                </div>
            </x-filament::card>

            @if ($periodFilter !== 'annual' && $this->selectedQuarterStatus)
                <div class="mt-2 px-1 text-[11px] text-gray-400">
                    Due-date reminders use the standard ATO quarterly date unless a BAS period has an explicit due-date override.
                    The due date shown on your ATO activity statement takes precedence.
                </div>
            @endif
        </div>

        <div class="mt-6" x-data="{ showExtraBasFigures: false }">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                <div class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/10 dark:bg-white/[0.03]">
                    <div>
                        <div class="text-base font-semibold text-gray-950 dark:text-white">ATO BAS figures</div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Main GST figures laid out in the same order as the ATO form.</p>
                    </div>

                    <button type="button" x-on:click="showExtraBasFigures = ! showExtraBasFigures"
                            class="inline-flex w-fit items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 ring-1 ring-gray-950/10 transition hover:bg-white hover:text-gray-950 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-white/10 dark:hover:text-white">
                        <x-heroicon-o-adjustments-horizontal class="h-4 w-4"/>
                        <span x-text="showExtraBasFigures ? 'Hide extra fields' : 'Show extra fields'"></span>
                    </button>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-white/10">
                    {{-- G1 --}}
                    <button type="button" wire:click="openBreakdown('g1_total_sales')" class="grid w-full gap-y-3 px-5 py-4 text-left transition hover:bg-gray-50 dark:hover:bg-white/[0.03] sm:grid-cols-[3.5rem_11rem_minmax(0,1fr)_1.5rem] sm:items-center sm:gap-x-5">
                        <span class="inline-flex h-10 min-w-12 items-center justify-center rounded-lg border border-blue-400/40 bg-blue-500/10 px-3 text-base font-bold text-blue-700 dark:border-blue-400/30 dark:bg-blue-500/10 dark:text-blue-300">
                            G1
                        </span>

                        <div class="ml-10">
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-[#e87000]">Enter on BAS</div>
                            <div class="mt-0.5 text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                                ${{ number_format($bas['whole']['g1_total_sales']) }}
                            </div>
                            <div class="mt-0.5 text-[11px] text-gray-400">
                                exact ${{ number_format((float) $bas['g1_total_sales'], 2) }}
                            </div>
                        </div>

                        <div>
                            <div class="font-semibold text-gray-950 dark:text-white">Total sales</div>
                            <div class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                Gross business sales for the period, including GST where applicable.
                                {{ $bas['counts']['g1_total_sales'] }} {{ \Illuminate\Support\Str::plural('transaction', $bas['counts']['g1_total_sales']) }}.
                            </div>
                        </div>

                        <x-heroicon-o-chevron-right class="hidden h-4 w-4 text-gray-400 sm:block"/>
                    </button>

                    {{-- Does G1 include GST? --}}
                    <div class="grid gap-y-3 px-5 py-3 sm:grid-cols-[3.5rem_11rem_minmax(0,1fr)_1.5rem] sm:items-center sm:gap-x-5">
                        <span class="inline-flex h-9 min-w-12 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 px-3 text-xs font-bold text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                            GST?
                        </span>

                        <div class="ml-10">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-3 py-1.5 text-sm font-semibold text-success-700 dark:bg-success-500/10 dark:text-success-300">
                                <x-heroicon-o-check class="h-4 w-4"/>
                                Yes
                            </span>
                        </div>

                        <div>
                            <div class="font-medium text-gray-950 dark:text-white">Does G1 include GST?</div>
                            <div class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                Yes — G1 uses the gross sale amount before separating the GST shown at 1A.
                            </div>
                        </div>

                        <div></div>
                    </div>

                    {{-- 1A --}}
                    <button type="button" wire:click="openBreakdown('gst_1a')" class="grid w-full gap-y-3 px-5 py-4 text-left transition hover:bg-gray-50 dark:hover:bg-white/[0.03] sm:grid-cols-[3.5rem_11rem_minmax(0,1fr)_1.5rem] sm:items-center sm:gap-x-5">
                        <span class="inline-flex h-10 min-w-12 items-center justify-center rounded-lg border border-blue-400/40 bg-blue-500/10 px-3 text-base font-bold text-blue-700 dark:border-blue-400/30 dark:bg-blue-500/10 dark:text-blue-300">
                            1A
                        </span>

                        <div class="ml-10">
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-[#e87000]">Enter on BAS</div>
                            <div class="mt-0.5 text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                                ${{ number_format($bas['whole']['gst_1a']) }}
                            </div>
                            <div class="mt-0.5 text-[11px] text-gray-400">
                                exact ${{ number_format((float) $bas['gst_1a'], 2) }}
                            </div>
                        </div>

                        <div>
                            <div class="font-semibold text-gray-950 dark:text-white">GST on sales</div>
                            <div class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                GST collected on taxable sales for the period.
                                {{ $bas['counts']['gst_1a'] }} {{ \Illuminate\Support\Str::plural('transaction', $bas['counts']['gst_1a']) }}.
                            </div>
                        </div>

                        <x-heroicon-o-chevron-right class="hidden h-4 w-4 text-gray-400 sm:block"/>
                    </button>

                    {{-- 1B --}}
                    <button type="button" wire:click="openBreakdown('gst_1b')" class="grid w-full gap-y-3 px-5 py-4 text-left transition hover:bg-gray-50 dark:hover:bg-white/[0.03] sm:grid-cols-[3.5rem_11rem_minmax(0,1fr)_1.5rem] sm:items-center sm:gap-x-5">
                        <span class="inline-flex h-10 min-w-12 items-center justify-center rounded-lg border border-blue-400/40 bg-blue-500/10 px-3 text-base font-bold text-blue-700 dark:border-blue-400/30 dark:bg-blue-500/10 dark:text-blue-300">
                            1B
                        </span>

                        <div class="ml-10">
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-[#e87000]">Enter on BAS</div>
                            <div class="mt-0.5 text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                                ${{ number_format($bas['whole']['gst_1b']) }}
                            </div>
                            <div class="mt-0.5 text-[11px] text-gray-400">
                                exact ${{ number_format((float) $bas['gst_1b'], 2) }}
                            </div>
                        </div>

                        <div>
                            <div class="font-semibold text-gray-950 dark:text-white">GST on purchases</div>
                            <div class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                GST credits claimable on business purchases for the period.
                                {{ $bas['counts']['gst_1b'] }} {{ \Illuminate\Support\Str::plural('transaction', $bas['counts']['gst_1b']) }}.
                            </div>
                        </div>

                        <x-heroicon-o-chevron-right class="hidden h-4 w-4 text-gray-400 sm:block"/>
                    </button>

                    {{-- Amount payable/refund --}}
                    <div class="grid gap-y-3 bg-gray-50 px-5 py-4 dark:bg-white/[0.03] sm:grid-cols-[3.5rem_11rem_minmax(0,1fr)_1.5rem] sm:items-center sm:gap-x-5">
                        <span class="inline-flex h-10 min-w-12 items-center justify-center rounded-lg border border-blue-400/40 bg-blue-500/10 px-3 text-base font-bold text-blue-700 dark:border-blue-400/30 dark:bg-blue-500/10 dark:text-blue-300">
                            9
                        </span>

                        <div class="ml-10">
                            <div @class([
                                'text-[10px] font-semibold uppercase tracking-wider',
                                'text-danger-600 dark:text-danger-400' => $netPayable && (float) $bas['net_amount'] > 0,
                                'text-success-600 dark:text-success-400' => ! $netPayable || (float) $bas['net_amount'] === 0.0,
                            ])>
                                {{ $netPayable ? 'Payable' : 'Refund' }}
                            </div>
                            <div @class([
                                'mt-0.5 text-2xl font-bold tracking-tight',
                                'text-danger-600 dark:text-danger-400' => $netPayable && (float) $bas['net_amount'] > 0,
                                'text-success-600 dark:text-success-400' => ! $netPayable || (float) $bas['net_amount'] === 0.0,
                            ])>
                                ${{ number_format(abs($bas['whole']['net_amount'])) }}
                            </div>
                            <div class="mt-0.5 text-[11px] text-gray-400">
                                exact {{ $bas['net_amount'] < 0 ? '-' : '' }}${{ number_format(abs((float) $bas['net_amount']), 2) }}
                            </div>
                        </div>

                        <div>
                            <div class="font-semibold text-gray-950 dark:text-white">
                                {{ $netPayable ? 'Amount payable to ATO' : 'Refund from ATO' }}
                            </div>
                            <div class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                Whole-dollar BAS result calculated as 1A minus 1B.
                            </div>
                        </div>

                        <div></div>
                    </div>
                </div>
            </div>

        {{-- Extra worksheet fields --}}
        <div x-cloak x-show="showExtraBasFigures" x-transition.opacity class="mt-5">
            <div class="mb-3 flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Extra BAS calculation fields</h2>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Useful for audit and reconciliation, but not normally needed on your current ATO GST entry screen.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    'g2_export_sales' => ['G2', 'Export sales'],
                    'g3_gst_free_sales' => ['G3', 'Other GST-free sales'],
                    'g10_capital_purchases' => ['G10', 'Capital purchases'],
                    'g11_non_capital_purchases' => ['G11', 'Non-capital purchases'],
                ] as $field => [$code, $title])
                    <button type="button" wire:click="openBreakdown('{{ $field }}')" class="rounded-xl border border-gray-200 bg-white p-4 text-left transition hover:border-[#e87000]/50 hover:bg-gray-50 dark:border-white/10 dark:bg-gray-900 dark:hover:border-[#e87000]/50 dark:hover:bg-white/[0.03]">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-xs font-bold text-[#e87000]">{{ $code }}</div>
                                <div class="mt-0.5 font-medium text-gray-950 dark:text-white">{{ $title }}</div>
                            </div>
                            <x-heroicon-o-chevron-right class="h-4 w-4 shrink-0 text-gray-400"/>
                        </div>

                        <div class="mt-4">
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-[#e87000]">BAS figure</div>
                            <div class="mt-0.5 text-2xl font-bold text-gray-950 dark:text-white">
                                ${{ number_format($bas['whole'][$field]) }}
                            </div>
                            <div class="mt-1 text-xs text-gray-400">
                                Books exact ${{ number_format((float) $bas[$field], 2) }}
                            </div>
                        </div>

                        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                            {{ $bas['counts'][$field] }} {{ \Illuminate\Support\Str::plural('transaction', $bas['counts'][$field]) }}
                        </div>
                    </button>
                @endforeach
            </div>

            <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4 text-xs leading-5 text-gray-500 dark:border-white/10 dark:bg-white/[0.03] dark:text-gray-400">
                G10/G11 use the stored <span class="font-medium text-gray-700 dark:text-gray-200">business-use amount</span>.
                GST-free purchases are still included; transactions explicitly marked Excluded from BAS are not.
            </div>
        </div>
        </div>

        @if ($this->validation)
            <x-filament::card class="mt-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="text-base font-semibold text-gray-950 dark:text-white">Saved BAS comparison</div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Compare the current live transaction calculation with the saved {{ $this->savedBasPeriod->status }} snapshot.
                        </p>
                    </div>

                    @if ($this->validation['all_match'])
                        <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-success-50 px-3 py-1.5 text-xs font-semibold text-success-700 dark:bg-success-500/10 dark:text-success-300">
                            <x-heroicon-o-check-circle class="h-4 w-4"/>
                            All figures match
                        </span>
                    @else
                        <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-danger-50 px-3 py-1.5 text-xs font-semibold text-danger-700 dark:bg-danger-500/10 dark:text-danger-300">
                            <x-heroicon-o-exclamation-triangle class="h-4 w-4"/>
                            Differences found
                        </span>
                    @endif
                </div>

                <div class="mt-5 overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                    <table class="w-full min-w-[650px] text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Label</th>
                            <th class="px-4 py-3 text-right">Saved</th>
                            <th class="px-4 py-3 text-right">Live Books</th>
                            <th class="px-4 py-3 text-right">Difference</th>
                            <th class="w-16 px-4 py-3"></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($this->validation['rows'] as $row)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gray-950 dark:text-white">{{ $row['label'] }}</td>
                                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">${{ number_format($row['saved'], 2) }}</td>
                                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">${{ number_format($row['live'], 2) }}</td>
                                <td @class([
                                        'px-4 py-3 text-right font-medium',
                                        'text-success-600 dark:text-success-400' => $row['matches'],
                                        'text-danger-600 dark:text-danger-400' => ! $row['matches'],
                                    ])>
                                    {{ $row['difference'] > 0 ? '+' : ($row['difference'] < 0 ? '-' : '') }}${{ number_format(abs($row['difference']), 2) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($row['matches'])
                                        <x-heroicon-o-check class="ml-auto h-4 w-4 text-success-500"/>
                                    @else
                                        <x-heroicon-o-exclamation-circle class="ml-auto h-4 w-4 text-danger-500"/>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::card>
        @endif

        <section class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
            <div class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/10 dark:bg-white/[0.03]">
                <div>
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clock class="h-5 w-5 text-gray-400"/>
                        <h2 class="font-semibold text-gray-950 dark:text-white">BAS history</h2>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Review quarterly BAS figures across {{ $this->financialYears->count() }}
                        {{ \Illuminate\Support\Str::plural('financial year', $this->financialYears->count()) }}.
                        Select a row to open that quarter above.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="$toggle('showBasHistory')"
                    class="inline-flex w-fit shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 ring-1 ring-gray-950/10 transition hover:bg-white hover:text-gray-950 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-white/10 dark:hover:text-white"
                >
                    <x-heroicon-o-clock class="h-4 w-4"/>
                    {{ $showBasHistory ? 'Hide history' : 'Show history' }}
                </button>
            </div>

            @if ($showBasHistory)
                @php($history = $this->basHistory)

                <div class="border-b border-gray-200 bg-white px-5 py-2.5 text-[11px] text-gray-400 dark:border-white/10 dark:bg-gray-900">
                    <span class="font-medium text-gray-500 dark:text-gray-300">Snapshot</span> = saved lodged/historical record
                    · <span class="font-medium text-gray-500 dark:text-gray-300">Locked Books</span> = calculated from a closed financial year
                    · <span class="font-medium text-gray-500 dark:text-gray-300">Live Books</span> = current calculation
                </div>

                <div class="max-h-[34rem] overflow-auto">
                    <table class="w-full min-w-[920px] text-sm">
                        <thead class="sticky top-0 z-10 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 shadow-sm dark:bg-gray-900 dark:text-gray-400">
                            <tr>
                                <th class="px-5 py-3">Period</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Lodged / due</th>
                                <th class="px-4 py-3 text-right">1A</th>
                                <th class="px-4 py-3 text-right">1B</th>
                                <th class="px-4 py-3 text-right">Payable / refund</th>
                                <th class="w-12 px-4 py-3"></th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse ($history as $row)
                                <tr
                                    wire:key="bas-history-{{ $row['financial_year_id'] }}-{{ $row['key'] }}"
                                    wire:click="selectBasHistoryPeriod({{ $row['financial_year_id'] }}, '{{ $row['key'] }}')"
                                    x-on:click="setTimeout(() => window.scrollTo({ top: 0, behavior: 'smooth' }), 140)"
                                    @class([
                                        'cursor-pointer transition',
                                        'bg-orange-50/70 dark:bg-[#e87000]/[0.07]' => $row['selected'],
                                        'hover:bg-gray-50 dark:hover:bg-white/[0.03]' => ! $row['selected'],
                                    ])
                                    title="Open {{ $row['financial_year_label'] }} {{ $row['label'] }} BAS"
                                >
                                    <td class="px-5 py-3">
                                        <div class="font-semibold text-gray-950 dark:text-white">
                                            {{ $row['financial_year_label'] }} · {{ $row['label'] }}
                                        </div>
                                        <div class="mt-0.5 text-xs text-gray-400">
                                            {{ $row['start']->format('j M') }} – {{ $row['end']->format('j M Y') }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                            'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-300' => in_array($row['status'], ['lodged', 'historical'], true),
                                            'bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-300' => $row['status'] === 'overdue',
                                            'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-300' => in_array($row['status'], ['due_soon', 'reopened'], true),
                                            'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300' => in_array($row['status'], ['open', 'ready'], true),
                                        ])>
                                            {{ $row['status_label'] }}
                                        </span>
                                        <div class="mt-1 text-[10px] font-medium uppercase tracking-wide text-gray-400">
                                            {{ $row['figure_source'] }}
                                        </div>
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $row['date_label'] }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-gray-700 dark:text-gray-200">
                                        ${{ number_format($row['whole_1a']) }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-gray-700 dark:text-gray-200">
                                        ${{ number_format($row['whole_1b']) }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        @if ($row['whole_net'] > 0)
                                            <span class="font-semibold tabular-nums text-danger-600 dark:text-danger-400">
                                                Payable ${{ number_format($row['whole_net']) }}
                                            </span>
                                        @elseif ($row['whole_net'] < 0)
                                            <span class="font-semibold tabular-nums text-success-600 dark:text-success-400">
                                                Refund ${{ number_format(abs($row['whole_net'])) }}
                                            </span>
                                        @else
                                            <span class="font-semibold tabular-nums text-gray-500 dark:text-gray-400">Nil $0</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-right">
                                        <x-heroicon-o-chevron-right @class([
                                            'ml-auto h-4 w-4',
                                            'text-[#e87000]' => $row['selected'],
                                            'text-gray-400' => ! $row['selected'],
                                        ])/>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-12 text-center text-gray-500 dark:text-gray-400">
                                        No quarterly BAS history is available yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <div class="mt-6 rounded-xl border border-gray-200 bg-gray-50 p-4 text-xs leading-5 text-gray-500 dark:border-white/10 dark:bg-white/[0.03] dark:text-gray-400">
            Books calculates the full G1/G2/G3/G10/G11/1A/1B worksheet so the figures remain auditable and comparable with QuickBAS.
            Depending on your GST reporting method, your actual BAS may require only a subset of these labels.
            The small “BAS” figures shown above drop cents to whole dollars; the larger values retain the exact Books calculation.
            “Annual” is a full-year check and is not necessarily an annual GST lodgment period.
        </div>
    @endif


        {{-- Dedicated white-paper BAS record. Lodged/historical periods use their saved snapshot. --}}
        @php($printBusiness = $this->getBookBusiness())
        @php($printBas = $this->printBas)
        <section id="books-bas-print" class="books-bas-print">
            <div class="bpr-header">
                <div>
                    <div class="bpr-business">{{ $printBusiness->legal_name ?: $printBusiness->name }}</div>
                    @if ($printBusiness->legal_name && $printBusiness->legal_name !== $printBusiness->name)
                        <div class="bpr-meta">Trading as {{ $printBusiness->name }}</div>
                    @endif
                    @if ($printBusiness->abn)
                        <div class="bpr-meta">ABN {{ $printBusiness->abn }}</div>
                    @endif
                </div>

                <div>
                    <div class="bpr-title">Business Activity Statement</div>
                    <div class="bpr-period">{{ $bounds['label'] }}</div>
                    <div class="bpr-meta" style="text-align:right; margin-top:3px;">
                        Generated {{ now('Australia/Hobart')->format('j M Y') }}
                    </div>
                </div>
            </div>

            <div class="bpr-status">
                <div>
                    <div class="bpr-label">Status</div>
                    <div class="bpr-value">{{ $printBas['status_label'] }}</div>
                </div>
                <div>
                    <div class="bpr-label">Record source</div>
                    <div class="bpr-value">{{ $printBas['source_label'] }}</div>
                </div>
                <div>
                    <div class="bpr-label">
                        @if ($printBas['lodged_at'])
                            Lodged
                        @elseif ($printBas['due_date'])
                            Due
                        @else
                            Period
                        @endif
                    </div>
                    <div class="bpr-value">
                        @if ($printBas['lodged_at'])
                            {{ $printBas['lodged_at']->timezone('Australia/Hobart')->format('j M Y') }}
                        @elseif ($printBas['due_date'])
                            {{ \Illuminate\Support\Carbon::parse($printBas['due_date'])->format('j M Y') }}
                        @else
                            {{ $bounds['start']->format('j M Y') }} – {{ $bounds['end']->format('j M Y') }}
                        @endif
                    </div>
                </div>
            </div>

            <div class="bpr-note">
                Whole-dollar BAS figures are shown in the <strong>BAS amount</strong> column.
                Exact Books values are shown for reconciliation.
                @if ($printBas['uses_snapshot'])
                    This printout uses the saved BAS snapshot rather than recalculating the lodged/historical figures.
                @else
                    This printout uses the current live Books calculation.
                @endif
            </div>

            <div class="bpr-section">
                <div class="bpr-heading">Main GST labels</div>
                <table class="bpr-table">
                    <tbody>
                        <tr>
                            <td class="bpr-code">G1</td>
                            <td class="bpr-desc">Total sales</td>
                            <td class="bpr-whole">${{ number_format($printBas['whole']['g1_total_sales']) }}</td>
                            <td class="bpr-exact">exact ${{ number_format($printBas['g1_total_sales'], 2) }}</td>
                        </tr>
                        <tr>
                            <td class="bpr-code">1A</td>
                            <td class="bpr-desc">GST on sales</td>
                            <td class="bpr-whole">${{ number_format($printBas['whole']['gst_1a']) }}</td>
                            <td class="bpr-exact">exact ${{ number_format($printBas['gst_1a'], 2) }}</td>
                        </tr>
                        <tr>
                            <td class="bpr-code">1B</td>
                            <td class="bpr-desc">GST on purchases</td>
                            <td class="bpr-whole">${{ number_format($printBas['whole']['gst_1b']) }}</td>
                            <td class="bpr-exact">exact ${{ number_format($printBas['gst_1b'], 2) }}</td>
                        </tr>
                        <tr class="bpr-net">
                            <td class="bpr-code">9</td>
                            <td class="bpr-desc">
                                {{ $printBas['whole']['net_amount'] >= 0 ? 'Amount payable to ATO' : 'Refund from ATO' }}
                            </td>
                            <td class="bpr-whole">
                                {{ $printBas['whole']['net_amount'] < 0 ? '-' : '' }}${{ number_format(abs($printBas['whole']['net_amount'])) }}
                            </td>
                            <td class="bpr-exact">
                                exact {{ $printBas['net_amount'] < 0 ? '-' : '' }}${{ number_format(abs($printBas['net_amount']), 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="bpr-section">
                <div class="bpr-heading">Supporting BAS labels</div>
                <table class="bpr-table">
                    <tbody>
                        <tr>
                            <td class="bpr-code">G2</td>
                            <td class="bpr-desc">Export sales</td>
                            <td class="bpr-whole">${{ number_format($printBas['whole']['g2_export_sales']) }}</td>
                            <td class="bpr-exact">exact ${{ number_format($printBas['g2_export_sales'], 2) }}</td>
                        </tr>
                        <tr>
                            <td class="bpr-code">G3</td>
                            <td class="bpr-desc">Other GST-free sales</td>
                            <td class="bpr-whole">${{ number_format($printBas['whole']['g3_gst_free_sales']) }}</td>
                            <td class="bpr-exact">exact ${{ number_format($printBas['g3_gst_free_sales'], 2) }}</td>
                        </tr>
                        <tr>
                            <td class="bpr-code">G10</td>
                            <td class="bpr-desc">Capital purchases</td>
                            <td class="bpr-whole">${{ number_format($printBas['whole']['g10_capital_purchases']) }}</td>
                            <td class="bpr-exact">exact ${{ number_format($printBas['g10_capital_purchases'], 2) }}</td>
                        </tr>
                        <tr>
                            <td class="bpr-code">G11</td>
                            <td class="bpr-desc">Non-capital purchases</td>
                            <td class="bpr-whole">${{ number_format($printBas['whole']['g11_non_capital_purchases']) }}</td>
                            <td class="bpr-exact">exact ${{ number_format($printBas['g11_non_capital_purchases'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="bpr-footer">
                Prepared from ClientBill Books for {{ $printBusiness->legal_name ?: $printBusiness->name }}.
                Lodged and historical BAS records are printed from their stored snapshot so later transaction changes cannot silently alter the record presented here.
            </div>
        </section>

    <x-filament::modal id="bookBasLodgeConfirmation" width="lg">
        <x-slot name="heading">Mark BAS as lodged?</x-slot>
        <x-slot name="description">
            This saves the BAS snapshot and locks transactions dated inside this quarter.
        </x-slot>

        @if ($this->periodBounds && $periodFilter !== 'annual')
            <div class="space-y-4">
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                    <div class="text-sm font-semibold text-gray-950 dark:text-white">{{ $this->periodBounds['label'] }}</div>

                    <div class="mt-4 grid grid-cols-2 gap-x-5 gap-y-3 text-sm">
                        <div class="text-gray-500 dark:text-gray-400">G1 Total sales</div>
                        <div class="text-right font-semibold text-gray-950 dark:text-white">${{ number_format($this->bas['whole']['g1_total_sales']) }}</div>

                        <div class="text-gray-500 dark:text-gray-400">1A GST on sales</div>
                        <div class="text-right font-semibold text-gray-950 dark:text-white">${{ number_format($this->bas['whole']['gst_1a']) }}</div>

                        <div class="text-gray-500 dark:text-gray-400">1B GST on purchases</div>
                        <div class="text-right font-semibold text-gray-950 dark:text-white">${{ number_format($this->bas['whole']['gst_1b']) }}</div>

                        <div class="border-t border-gray-200 pt-3 font-medium text-gray-700 dark:border-white/10 dark:text-gray-200">
                            {{ $this->bas['whole']['net_amount'] >= 0 ? 'Amount payable' : 'Refund' }}
                        </div>
                        <div @class([
                            'border-t border-gray-200 pt-3 text-right text-lg font-bold dark:border-white/10',
                            'text-danger-600 dark:text-danger-400' => $this->bas['whole']['net_amount'] >= 0,
                            'text-success-600 dark:text-success-400' => $this->bas['whole']['net_amount'] < 0,
                        ])>
                            ${{ number_format(abs($this->bas['whole']['net_amount'])) }}
                        </div>
                    </div>
                </div>

                <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">
                    All G1/G2/G3/G10/G11/1A/1B exact figures are also saved in the snapshot.
                    To correct a transaction later, reopen this BAS first and then re-lodge it after the correction.
                </p>

                <div class="flex justify-end gap-2">
                    <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'bookBasLodgeConfirmation' })">
                        Cancel
                    </x-filament::button>
                    <x-filament::button wire:click="lodgeBas" icon="heroicon-o-lock-closed">
                        Confirm lodged
                    </x-filament::button>
                </div>
            </div>
        @endif
    </x-filament::modal>

    <x-filament::modal id="bookBasReopenConfirmation" width="lg">
        <x-slot name="heading">Reopen this BAS period?</x-slot>
        <x-slot name="description">
            Reopening unlocks the quarter so transactions can be corrected.
        </x-slot>

        @if ($this->savedBasPeriod?->status === 'lodged')
            <div class="space-y-4">
                <div class="rounded-xl border border-warning-200 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-200">
                    {{ $this->savedBasPeriod->period_label }} was lodged
                    @if ($this->savedBasPeriod->lodged_at)
                        on {{ $this->savedBasPeriod->lodged_at->timezone('Australia/Hobart')->format('j M Y') }}
                    @endif.
                    Its saved snapshot is kept for comparison while you make corrections.
                </div>

                <p class="text-xs leading-5 text-gray-500 dark:text-gray-400">
                    After changing transactions, return here and use <strong>Re-lodge BAS</strong> to replace the saved snapshot and lock the period again.
                </p>

                <div class="flex justify-end gap-2">
                    <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'bookBasReopenConfirmation' })">
                        Cancel
                    </x-filament::button>
                    <x-filament::button color="warning" wire:click="reopenBas" icon="heroicon-o-lock-open">
                        Reopen BAS
                    </x-filament::button>
                </div>
            </div>
        @endif
    </x-filament::modal>

    <x-filament::modal id="bookBasBreakdown" width="5xl" slide-over>
        @if ($this->breakdownDefinition)
            <x-slot name="heading">
                {{ $this->breakdownDefinition['code'] }} · {{ $this->breakdownDefinition['title'] }}
            </x-slot>

            <x-slot name="description">
                {{ $this->breakdownDefinition['description'] }}
            </x-slot>

            <div class="mb-4 flex items-center justify-between gap-4 rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">Period</div>
                    <div class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-200">{{ $this->periodBounds['label'] }}</div>
                </div>
                <div class="text-right">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-[#e87000]">BAS figure</div>
                    <div class="mt-0.5 text-2xl font-bold text-gray-950 dark:text-white">
                        ${{ number_format($this->bas['whole'][$breakdownLabel] ?? 0) }}
                    </div>
                    <div class="mt-1 text-xs text-gray-400">
                        Books exact ${{ number_format($this->breakdownTotal, 2) }}
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                <div class="max-h-[65vh] overflow-auto">
                    <table class="w-full min-w-[760px] text-sm">
                        <thead class="sticky top-0 z-10 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3 text-right">Business amount</th>
                            <th class="px-4 py-3 text-right">GST</th>
                            <th class="px-4 py-3 text-right">Contribution</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($this->breakdownTransactions as $transaction)
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-white/[0.03]">
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $transaction->transaction_date->format('j M Y') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-200">
                                    {{ $transaction->category?->name ?? 'No category' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-950 dark:text-white">{{ $transaction->description ?: 'No description' }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-600 dark:text-gray-300">
                                    {{ (float) $transaction->business_amount < 0 ? '-' : '+' }}${{ number_format(abs((float) $transaction->business_amount), 2) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-500 dark:text-gray-400">
                                    {{ (float) $transaction->gst_amount > 0 ? '$' . number_format((float) $transaction->gst_amount, 2) : '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-gray-950 dark:text-white">
                                    ${{ number_format($this->breakdownContribution($transaction), 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    No transactions contribute to this BAS label.
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
