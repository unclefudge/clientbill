<x-filament-panels::page>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">QuickBAS History</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Bring a completed QuickBAS worksheet into Books without re-categorising the old transactions.
            </p>
        </div>

        <x-filament::button
            color="gray"
            icon="heroicon-o-question-mark-circle"
            x-on:click="$dispatch('open-modal', { id: 'bookQuickBasHelp' })"
        >
            Help
        </x-filament::button>
    </div>

    <div class="mt-6 space-y-6">
        @if (! $this->booksStorageReady)
            <div class="flex flex-col gap-3 rounded-xl border border-warning-300 bg-warning-50 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-warning-500/30 dark:bg-warning-500/10">
                <div>
                    <div class="font-semibold text-warning-800 dark:text-warning-200">DigitalOcean Spaces archiving is not ready yet</div>
                    <p class="mt-1 text-sm text-warning-700 dark:text-warning-300">
                        You can analyse and migrate the QuickBAS data, but the source exports and BAS PDFs will not be archived until the S3 filesystem adapter and BOOKS_SPACES_* settings are configured.
                    </p>
                </div>
                <span class="shrink-0 rounded-full bg-warning-100 px-3 py-1.5 text-xs font-semibold text-warning-800 dark:bg-warning-500/20 dark:text-warning-200">Data import still works</span>
            </div>
        @else
            <div class="flex items-center gap-3 rounded-xl border border-success-300 bg-success-50 p-4 dark:border-success-500/30 dark:bg-success-500/10">
                <x-heroicon-o-cloud-arrow-up class="h-5 w-5 shrink-0 text-success-600 dark:text-success-400" />
                <div class="text-sm text-success-800 dark:text-success-200">
                    Source exports and any BAS PDFs supplied below will be archived privately to DigitalOcean Spaces when the migration is completed.
                </div>
            </div>
        @endif

        <x-filament::card>
            <div class="space-y-6">
                <div>
                    <div class="text-base font-semibold text-gray-950 dark:text-white">1. Choose the QuickBAS exports</div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Export the Income and Expenses reports for one QuickBAS worksheet/financial year. The year and categories are detected from the files.
                    </p>
                </div>

                <div class="grid gap-5 lg:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Income export <span class="text-danger-500">*</span></label>
                        <input
                            type="file"
                            wire:model="incomeFile"
                            accept=".txt,.csv,text/plain,text/csv"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:file:bg-white/10 dark:file:text-gray-200 dark:hover:file:bg-white/20 dark:hover:file:text-white"
                        />
                        <div wire:loading wire:target="incomeFile" class="mt-1.5 text-xs text-primary-600 dark:text-primary-400">Reading income file…</div>
                        @error('incomeFile') <div class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Expenses export <span class="text-danger-500">*</span></label>
                        <input
                            type="file"
                            wire:model="expenseFile"
                            accept=".txt,.csv,text/plain,text/csv"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:file:bg-white/10 dark:file:text-gray-200 dark:hover:file:bg-white/20 dark:hover:file:text-white"
                        />
                        <div wire:loading wire:target="expenseFile" class="mt-1.5 text-xs text-primary-600 dark:text-primary-400">Reading expenses file…</div>
                        @error('expenseFile') <div class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-5 dark:border-white/10">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">BAS PDFs <span class="font-normal text-gray-500 dark:text-gray-400">(optional)</span></label>
                    <input
                        type="file"
                        wire:model="basFiles"
                        accept=".pdf,application/pdf"
                        multiple
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:file:bg-white/10 dark:file:text-gray-200 dark:hover:file:bg-white/20 dark:hover:file:text-white"
                    />
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">You can select the annual and/or quarterly QuickBAS BAS PDFs. They are archived as source documents; the historical BAS numbers are calculated from the imported transaction data.</p>
                    <div wire:loading wire:target="basFiles" class="mt-1.5 text-xs text-primary-600 dark:text-primary-400">Reading BAS document(s)…</div>
                    @error('basFiles.*') <div class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</div> @enderror
                </div>

                @error('quickbas')
                    <div class="rounded-xl border border-danger-300 bg-danger-50 p-4 text-sm font-medium text-danger-700 dark:border-danger-500/30 dark:bg-danger-500/10 dark:text-danger-300">{{ $message }}</div>
                @enderror

                <div class="flex justify-end">
                    <x-filament::button
                        wire:click="analyseFiles"
                        wire:loading.attr="disabled"
                        wire:target="analyseFiles,incomeFile,expenseFile"
                        icon="heroicon-o-magnifying-glass"
                    >
                        Analyse Files
                    </x-filament::button>
                </div>
            </div>
        </x-filament::card>

        @if ($analysisPreview !== [])
            @php($fy = $analysisPreview['financial_year'])
            @php($bas = $analysisPreview['bas'])
            @php($allChecksPass = collect($analysisPreview['checks'])->every(fn ($check) => $check === true))

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-filament::card>
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Financial year</div>
                    <div class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{{ $fy['label'] }}</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($fy['start_date'])->format('j M Y') }} – {{ \Carbon\Carbon::parse($fy['end_date'])->format('j M Y') }}</div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Income</div>
                    <div class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{{ $analysisPreview['income_count'] }}</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">${{ number_format($analysisPreview['source_totals']['income']['income_total'], 2) }} total income</div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Expenses</div>
                    <div class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{{ $analysisPreview['expense_count'] }}</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">${{ number_format($analysisPreview['source_totals']['expenses']['business_cost'], 2) }} business cost</div>
                </x-filament::card>

                <x-filament::card>
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Validation</div>
                    <div class="mt-2 flex items-center gap-2 text-lg font-bold {{ $allChecksPass ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                        @if ($allChecksPass)
                            <x-heroicon-o-check-circle class="h-6 w-6" />
                            Source totals match
                        @else
                            <x-heroicon-o-exclamation-triangle class="h-6 w-6" />
                            Check totals
                        @endif
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $analysisPreview['total_count'] }} transactions will be migrated.</div>
                </x-filament::card>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <x-filament::card>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-base font-semibold text-gray-950 dark:text-white">2. Categories</div>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Existing categories are matched; missing ones are created automatically.</p>
                        </div>
                        @php($createCount = collect($analysisPreview['categories'])->where('action', 'create')->count())
                        @php($matchCount = collect($analysisPreview['categories'])->where('action', 'match')->count())
                        <div class="text-right text-xs text-gray-500 dark:text-gray-400">
                            <div><span class="font-semibold text-primary-600 dark:text-primary-400">{{ $createCount }}</span> create</div>
                            <div><span class="font-semibold text-success-600 dark:text-success-400">{{ $matchCount }}</span> match</div>
                        </div>
                    </div>

                    <div class="mt-4 max-h-[430px] overflow-y-auto rounded-xl border border-gray-200 dark:border-white/10">
                        <table class="w-full text-left text-sm">
                            <thead class="sticky top-0 bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">Category</th>
                                    <th class="px-4 py-3">Type</th>
                                    <th class="px-4 py-3 text-right">Rows</th>
                                    <th class="px-4 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                                @foreach ($analysisPreview['categories'] as $category)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-950 dark:text-white">{{ $category['name'] }}</div>
                                            <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                                {{ number_format($category['default_business_use_percentage'], 0) }}% business
                                                · {{ $category['default_gst_treatment'] === 'gst_applicable' ? 'GST applies' : 'GST free' }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ ucfirst($category['type']) }}</td>
                                        <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">{{ $category['count'] }}</td>
                                        <td class="px-4 py-3">
                                            @if ($category['action'] === 'match')
                                                <span class="inline-flex rounded-full bg-success-50 px-2 py-1 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-300">Match existing</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">Create</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div>
                        <div class="text-base font-semibold text-gray-950 dark:text-white">3. Historical BAS check</div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Calculated from the QuickBAS transaction rows before anything is imported.</p>
                    </div>

                    <div class="mt-5 space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">G1 Total sales</span><span class="font-semibold text-gray-950 dark:text-white">${{ number_format($bas['g1_total_sales'], 2) }}</span></div>
                        <div class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">G2 Export sales</span><span class="font-semibold text-gray-950 dark:text-white">${{ number_format($bas['g2_export_sales'], 2) }}</span></div>
                        <div class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">G3 GST-free sales</span><span class="font-semibold text-gray-950 dark:text-white">${{ number_format($bas['g3_gst_free_sales'], 2) }}</span></div>
                        <div class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">G10 Capital purchases</span><span class="font-semibold text-gray-950 dark:text-white">${{ number_format($bas['g10_capital_purchases'], 2) }}</span></div>
                        <div class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">G11 Non-capital purchases</span><span class="font-semibold text-gray-950 dark:text-white">${{ number_format($bas['g11_non_capital_purchases'], 2) }}</span></div>
                        <div class="border-t border-gray-200 pt-3 dark:border-white/10 flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">1A GST on sales</span><span class="font-semibold text-gray-950 dark:text-white">${{ number_format($bas['gst_1a'], 2) }}</span></div>
                        <div class="flex justify-between gap-4"><span class="text-gray-500 dark:text-gray-400">1B GST on purchases</span><span class="font-semibold text-gray-950 dark:text-white">${{ number_format($bas['gst_1b'], 2) }}</span></div>
                        <div class="border-t border-gray-200 pt-3 dark:border-white/10 flex justify-between gap-4"><span class="font-semibold text-gray-700 dark:text-gray-200">Net {{ $bas['net_amount'] >= 0 ? 'payable' : 'credit' }}</span><span class="text-lg font-bold {{ $bas['net_amount'] >= 0 ? 'text-primary-600 dark:text-primary-400' : 'text-success-600 dark:text-success-400' }}">${{ number_format(abs($bas['net_amount']), 2) }}</span></div>
                    </div>

                    <div class="mt-5 rounded-xl bg-gray-50 p-4 text-xs text-gray-600 dark:bg-white/5 dark:text-gray-300">
                        Books keeps extra precision for percentage business-use amounts before the final total is rounded. This is needed to reproduce QuickBAS totals where an individual displayed row has already been rounded to cents.
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-2 text-xs">
                        @foreach ($analysisPreview['checks'] as $name => $passed)
                            <div class="flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 dark:bg-white/5">
                                @if ($passed)
                                    <x-heroicon-m-check-circle class="h-4 w-4 shrink-0 text-success-500" />
                                @else
                                    <x-heroicon-m-x-circle class="h-4 w-4 shrink-0 text-danger-500" />
                                @endif
                                <span class="text-gray-600 dark:text-gray-300">{{ str($name)->replace('_', ' ')->headline() }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-filament::card>
            </div>

            <x-filament::card>
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="text-base font-semibold text-gray-950 dark:text-white">Ready to migrate {{ $fy['label'] }}</div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            This creates the financial year if needed, creates/matches the categories, imports {{ $analysisPreview['total_count'] }} historical transactions, and saves an annual historical BAS summary.
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Historical rows go directly into Books as <strong>QuickBAS migration</strong> transactions; they do not enter the bank review queue.</p>
                    </div>

                    <x-filament::button
                        wire:click="importHistory"
                        wire:loading.attr="disabled"
                        wire:target="importHistory"
                        icon="heroicon-o-arrow-down-tray"
                        :disabled="! $allChecksPass"
                        class="shrink-0"
                    >
                        Import Historical Year
                    </x-filament::button>
                </div>
            </x-filament::card>
        @endif

        @if ($this->historicalMigrations->isNotEmpty())
            <x-filament::card>
                <div>
                    <div class="text-base font-semibold text-gray-950 dark:text-white">Imported QuickBAS history</div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Completed historical migrations for {{ $this->getBookBusiness()->name }}.</p>
                </div>

                <div class="mt-4 divide-y divide-gray-200 rounded-xl border border-gray-200 dark:divide-white/10 dark:border-white/10">
                    @foreach ($this->historicalMigrations as $migration)
                        <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="font-semibold text-gray-950 dark:text-white">
                                    {{ $migration->period_start?->format('Y') }}–{{ $migration->period_end?->format('y') }}
                                </div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $migration->included_count }} transactions · imported {{ $migration->imported_at?->format('j M Y g:i a') }}
                                </div>
                            </div>
                            <x-filament::button color="danger" size="sm" outlined wire:click="askToRemoveMigration({{ $migration->id }})">
                                Remove Migration
                            </x-filament::button>
                        </div>
                    @endforeach
                </div>
            </x-filament::card>
        @endif
    </div>

    <x-filament::modal id="bookQuickBasHelp" width="2xl">
        <x-slot name="heading">QuickBAS historical migration</x-slot>
        <div class="space-y-4 text-sm text-gray-600 dark:text-gray-300">
            <p>This tool is for completed historical QuickBAS worksheets. It does not replace the normal bank CSV import used for new transactions.</p>
            <div class="space-y-2">
                <div><strong>1.</strong> Export the Income and Expenses reports from the same QuickBAS financial year.</div>
                <div><strong>2.</strong> Optionally select that year's annual and quarterly BAS PDFs for archiving.</div>
                <div><strong>3.</strong> Analyse the files. Books verifies the row counts and financial totals before allowing the migration.</div>
                <div><strong>4.</strong> Complete the migration. Existing categories are matched and missing categories are created automatically.</div>
                <div><strong>5.</strong> The imported history immediately becomes available to the normal bank importer for category suggestions.</div>
            </div>
            <p>Removing a QuickBAS migration removes the transactions and historical annual BAS summary created by that migration, but deliberately leaves categories in place.</p>
        </div>
        <x-slot name="footer">
            <x-filament::button x-on:click="$dispatch('close-modal', { id: 'bookQuickBasHelp' })">Got it</x-filament::button>
        </x-slot>
    </x-filament::modal>

    <x-filament::modal id="bookQuickBasRemoveConfirm" width="lg">
        <x-slot name="heading">Remove QuickBAS migration?</x-slot>
        <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
            @if ($this->removingMigration)
                <p>This will remove the <strong>{{ $this->removingMigration->period_start?->format('Y') }}–{{ $this->removingMigration->period_end?->format('y') }}</strong> QuickBAS transactions, migration rows, archived source documents and historical annual BAS summary.</p>
            @endif
            <p>Categories created during the migration are kept so they are not accidentally removed if you have started using them elsewhere.</p>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'bookQuickBasRemoveConfirm' })">Cancel</x-filament::button>
                <x-filament::button color="danger" wire:click="removeMigration" wire:loading.attr="disabled" wire:target="removeMigration">Remove Migration</x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
