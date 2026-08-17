<x-filament-panels::page>
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Books Overview</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">A quick view of the current financial year and what needs attention.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-filament::card>
            <div class="text-sm font-semibold text-gray-500 dark:text-gray-400">Income YTD</div>
            <div class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">${{ number_format($incomeYtd, 2) }}</div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-sm font-semibold text-gray-500 dark:text-gray-400">Expenses YTD</div>
            <div class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">${{ number_format($expensesYtd, 2) }}</div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-sm font-semibold text-gray-500 dark:text-gray-400">Profit YTD</div>
            <div class="mt-2 text-3xl font-bold {{ $profitYtd < 0 ? 'text-red-500' : 'text-emerald-600 dark:text-emerald-400' }}">
                {{ $profitYtd < 0 ? '-' : '' }}${{ number_format(abs($profitYtd), 2) }}
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-sm font-semibold text-gray-500 dark:text-gray-400">Current BAS</div>
            <div class="mt-2 text-lg font-bold text-gray-950 dark:text-white">{{ $basPeriodLabel }}</div>
            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Estimated {{ $gstNet >= 0 ? 'payable' : 'credit' }}:
                <span class="font-semibold text-gray-900 dark:text-gray-100">${{ number_format(abs($gstNet), 2) }}</span>
            </div>
            @if ($basDueDate)
                <div class="mt-2 text-xs text-primary-600 dark:text-primary-400">Due {{ $basDueDate }}</div>
            @endif
        </x-filament::card>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 xl:grid-cols-2">
        <x-filament::card>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-base font-bold text-gray-950 dark:text-white">Transactions</div>
                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Items that still need a bookkeeping decision.</div>
                </div>
                <a href="{{ \App\Filament\Pages\Books\BooksTransactions::getUrl() }}" wire:navigate class="text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400">View transactions</a>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-4">
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Uncategorised</div>
                    <div class="mt-1 text-2xl font-bold {{ $uncategorisedCount ? 'text-amber-500' : 'text-emerald-500' }}">{{ $uncategorisedCount }}</div>
                </div>
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Import rows to review</div>
                    <div class="mt-1 text-2xl font-bold {{ $pendingImportCount ? 'text-amber-500' : 'text-emerald-500' }}">{{ $pendingImportCount }}</div>
                </div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-base font-bold text-gray-950 dark:text-white">BAS estimate</div>
            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Live from transactions in {{ $basPeriodLabel }}.</div>

            <div class="mt-5 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">GST collected (1A)</span><span class="font-semibold text-gray-950 dark:text-white">${{ number_format($gstCollected, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">GST credits (1B)</span><span class="font-semibold text-gray-950 dark:text-white">${{ number_format($gstCredits, 2) }}</span></div>
                <div class="border-t border-gray-200 pt-3 dark:border-white/10 flex justify-between"><span class="font-semibold text-gray-700 dark:text-gray-200">Net</span><span class="font-bold {{ $gstNet < 0 ? 'text-emerald-500' : 'text-primary-600 dark:text-primary-400' }}">{{ $gstNet < 0 ? '-' : '' }}${{ number_format(abs($gstNet), 2) }}</span></div>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
