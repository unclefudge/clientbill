<x-filament-panels::page>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Import</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $importCount }} {{ \Illuminate\Support\Str::plural('import', $importCount) }}
                · {{ $pendingCount }} {{ \Illuminate\Support\Str::plural('row', $pendingCount) }} waiting for review
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-filament::button color="gray" icon="heroicon-o-question-mark-circle" x-on:click="$dispatch('open-modal', { id: 'bookImportHelp' })">
                Help
            </x-filament::button>

            <x-filament::button color="gray" icon="heroicon-o-building-library" wire:click="openBankAccountModal">
                Add Bank Account
            </x-filament::button>

            @if ($currentImportId)
                <x-filament::button icon="heroicon-o-plus" wire:click="newImport">
                    New Import
                </x-filament::button>
            @endif
        </div>
    </div>

    @if (! $currentImportId)
        <div class="mt-6 space-y-4">
            @if ($this->categories->isEmpty())
                <div class="flex flex-col gap-3 rounded-xl border border-warning-300 bg-warning-50 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-warning-500/30 dark:bg-warning-500/10">
                    <div>
                        <div class="font-semibold text-warning-800 dark:text-warning-200">No categories yet</div>
                        <p class="mt-1 text-sm text-warning-700 dark:text-warning-300">You can stage a bank file now, but business transactions need a category before the import can be completed.</p>
                    </div>
                    <x-filament::button tag="a" href="{{ $this->categoriesUrl }}" color="warning" icon="heroicon-o-tag" class="shrink-0">
                        Set up categories
                    </x-filament::button>
                </div>
            @endif

            <x-filament::card>
                <div class="space-y-6">
                    <div>
                        <div class="text-base font-semibold text-gray-950 dark:text-white">1. Choose the bank file</div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Import the CSV exactly as it comes from your bank. Personal transactions can be excluded during review.
                        </p>
                    </div>

                    @if ($this->bankAccounts->isEmpty())
                        <div class="rounded-xl border border-warning-300 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-200">
                            <div class="font-semibold">Add a bank account first</div>
                            <div class="mt-1">Books remembers the CSV format separately for each bank account.</div>
                            <button type="button" wire:click="openBankAccountModal" class="mt-3 font-semibold underline underline-offset-2">Add bank account</button>
                        </div>
                    @else
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">CSV file</label>
                                <input type="file" wire:model="csvFile" accept=".csv,.txt,text/csv,text/plain"
                                       class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:file:bg-white/10 dark:file:text-gray-200 dark:hover:file:bg-white/20 dark:hover:file:text-white"/>
                                <div wire:loading wire:target="csvFile" class="mt-1.5 text-xs text-primary-600 dark:text-primary-400">Reading file…</div>
                                @error('csvFile')
                                <div class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</div> @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Bank account</label>
                                <x-books.select model="selectedBankAccountId" :value="$selectedBankAccountId" placeholder="Select bank account"
                                                :options="$this->bankAccounts->mapWithKeys(fn ($account) => [$account->id => $account->name . ($account->last_four ? ' · ' . $account->last_four : '')])->all()"
                                />
                            </div>
                        </div>
                    @endif

                    @if ($csvFile && $rawPreviewRows !== [])
                        <div class="border-t border-gray-200 pt-6 dark:border-white/10">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div class="text-base font-semibold text-gray-950 dark:text-white">2. Confirm the column mapping</div>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        This mapping will be remembered for this bank account next time.
                                    </p>
                                </div>

                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                    <input type="checkbox" wire:model.live="hasHeader" class="rounded border-gray-300 !text-[#e87000] focus:!ring-[#e87000] dark:border-white/20 dark:bg-white/10" style="accent-color: #e87000;">
                                    File has a header row
                                </label>
                            </div>

                            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Date</label>
                                    <x-books.select model="dateColumn" :value="$dateColumn" :options="$this->columnOptions"/>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Amount</label>
                                    <x-books.select model="amountColumn" :value="$amountColumn" :options="$this->columnOptions"/>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</label>
                                    <x-books.select model="descriptionColumn" :value="$descriptionColumn" :options="$this->columnOptions"/>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Date format</label>
                                    <x-books.select model="dateFormat" :value="$dateFormat" :options="$this->dateFormats"/>
                                </div>

                                {{--}}
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Balance</label>
                                    <x-books.select model="balanceColumn" :value="$balanceColumn" :options="$this->columnOptions" allow-empty empty-label="Not included" />
                                </div>--}}
                            </div>

                            @error('mapping')
                            <div class="mt-3 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</div> @enderror
                        </div>
                    @endif

                    @if ($csvFile && $mappedPreviewRows !== [])
                        <div class="border-t border-gray-200 pt-6 dark:border-white/10">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-base font-semibold text-gray-950 dark:text-white">3. Preview</div>
                                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">The first few rows using the mapping above.</div>
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-[700px] text-left text-sm">
                                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                                        <tr>
                                            <th class="px-4 py-3">Date</th>
                                            <th class="px-4 py-3">Description</th>
                                            <th class="px-4 py-3 text-right">Amount</th>
                                            <th class="px-4 py-3 text-right">Balance</th>
                                            <th class="px-4 py-3">Check</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                                        @foreach ($mappedPreviewRows as $row)
                                            <tr>
                                                <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-200">{{ $row['transaction_date'] ?? '—' }}</td>
                                                <td class="max-w-lg px-4 py-3 text-gray-700 dark:text-gray-200">{{ $row['description'] }}</td>
                                                <td class="whitespace-nowrap px-4 py-3 text-right font-medium {{ ($row['amount'] ?? 0) >= 0 ? 'text-success-600 dark:text-success-400' : 'text-gray-950 dark:text-white' }}">
                                                    {{ $row['amount'] === null ? '—' : (($row['amount'] >= 0 ? '+' : '-') . '$' . number_format(abs($row['amount']), 2)) }}
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 text-right text-gray-500 dark:text-gray-400">
                                                    {{ $row['balance'] === null ? '—' : '$' . number_format($row['balance'], 2) }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    @if ($row['errors'] === [])
                                                        <span class="inline-flex rounded-full bg-success-50 px-2 py-1 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-300">Ready</span>
                                                    @else
                                                        <span class="inline-flex rounded-full bg-danger-50 px-2 py-1 text-xs font-medium text-danger-700 dark:bg-danger-500/10 dark:text-danger-300">{{ implode(', ', $row['errors']) }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    The bank rows are staged first. Nothing is added to your Books transactions until you finish the review.
                                </div>
                                <x-filament::button wire:click="startImport" wire:loading.attr="disabled" wire:target="startImport" icon="heroicon-o-arrow-right">
                                    Review Transactions
                                </x-filament::button>
                            </div>
                        </div>
                    @endif
                </div>
            </x-filament::card>

        </div>
    @else
        @php($import = $this->currentImport)
        @php($stats = $this->reviewStats)

        @if ($import)
            <div class="mt-6 space-y-6">
                @if ($this->categories->isEmpty() && $import->status === 'review')
                    <div class="flex flex-col gap-3 rounded-xl border border-warning-300 bg-warning-50 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-warning-500/30 dark:bg-warning-500/10">
                        <div>
                            <div class="font-semibold text-warning-800 dark:text-warning-200">Categories are needed before this import can be completed.</div>
                            <p class="mt-1 text-sm text-warning-700 dark:text-warning-300">Create the categories you need, then return here to finish reviewing the staged transactions.</p>
                        </div>
                        <x-filament::button tag="a" href="{{ $this->categoriesUrl }}" color="warning" icon="heroicon-o-tag" class="shrink-0">
                            Set up categories
                        </x-filament::button>
                    </div>
                @endif

                <x-filament::card>
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="text-lg font-semibold text-gray-950 dark:text-white">{{ $import->original_filename }}</div>
                                @if ($import->status === 'complete')
                                    <span class="inline-flex rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-700 dark:bg-success-500/10 dark:text-success-300">Completed</span>
                                @else
                                    <span class="inline-flex rounded-full bg-warning-50 px-2.5 py-1 text-xs font-semibold text-warning-700 dark:bg-warning-500/10 dark:text-warning-300">Review</span>
                                @endif
                            </div>
                            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $import->bankAccount?->name ?? 'Bank account' }}
                                @if ($import->period_start && $import->period_end)
                                    · {{ $import->period_start->format('j M Y') }} – {{ $import->period_end->format('j M Y') }}
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @if ($import->status === 'review')
                                <x-filament::button color="danger" icon="heroicon-o-trash" wire:click="askToDiscardImport({{ $import->id }})">
                                    Discard Import
                                </x-filament::button>
                            @endif
                            <x-filament::button color="gray" wire:click="newImport" icon="heroicon-o-plus">New Import</x-filament::button>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
                        <button type="button" wire:click="$set('reviewFilter', 'all')" class="rounded-xl border border-gray-200 p-3 text-left dark:border-white/10 {{ $reviewFilter === 'all' ? 'ring-2 ring-primary-500' : '' }}">
                            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Total</div>
                            <div class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">{{ $stats['total'] }}</div>
                        </button>
                        <button type="button" wire:click="$set('reviewFilter', 'needs_review')" class="rounded-xl border border-gray-200 p-3 text-left dark:border-white/10 {{ $reviewFilter === 'needs_review' ? 'ring-2 ring-primary-500' : '' }}">
                            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Need review</div>
                            <div class="mt-1 text-xl font-semibold {{ ($stats['pending'] + $stats['invalid']) > 0 ? 'text-warning-600 dark:text-warning-400' : 'text-success-600 dark:text-success-400' }}">{{ $stats['pending'] + $stats['invalid'] }}</div>
                        </button>
                        <button type="button" wire:click="$set('reviewFilter', 'ready')" class="rounded-xl border border-gray-200 p-3 text-left dark:border-white/10 {{ $reviewFilter === 'ready' ? 'ring-2 ring-primary-500' : '' }}">
                            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Ready</div>
                            <div class="mt-1 text-xl font-semibold text-primary-600 dark:text-primary-400">{{ $stats['ready'] + $stats['included'] }}</div>
                        </button>
                        <button type="button" wire:click="$set('reviewFilter', 'personal')" class="rounded-xl border border-gray-200 p-3 text-left dark:border-white/10 {{ $reviewFilter === 'personal' ? 'ring-2 ring-primary-500' : '' }}">
                            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Personal</div>
                            <div class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">{{ $stats['personal'] }}</div>
                        </button>
                        <button type="button" wire:click="$set('reviewFilter', 'duplicates')" class="rounded-xl border border-gray-200 p-3 text-left dark:border-white/10 {{ $reviewFilter === 'duplicates' ? 'ring-2 ring-primary-500' : '' }}">
                            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Duplicates</div>
                            <div class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">{{ $stats['duplicates'] }}</div>
                        </button>
                        <div class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
                            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Imported</div>
                            <div class="mt-1 text-xl font-semibold text-success-600 dark:text-success-400">{{ $stats['included'] }}</div>
                        </div>
                    </div>
                </x-filament::card>

                <x-filament::card>
                    @if ($import->status === 'review')
                        <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <div class="text-base font-semibold text-gray-950 dark:text-white">Review bank transactions</div>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Choose a category for business rows, or mark accidental personal spending as Personal. Suggested categories still need your confirmation.
                                </p>
                            </div>

                            <div class="w-full sm:w-52">
                                <x-books.select
                                    model="reviewFilter"
                                    :value="$reviewFilter"
                                    :options="[
                                        'all' => 'All rows',
                                        'needs_review' => 'Needs review',
                                        'ready' => 'Ready',
                                        'personal' => 'Personal',
                                        'duplicates' => 'Duplicates',
                                    ]"
                                />
                            </div>
                        </div>
                    @else
                        <div class="mb-5">
                            <div class="text-base font-semibold text-gray-950 dark:text-white">Completed import</div>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">The included rows below are now normal Books transactions.</p>
                        </div>
                    @endif

                    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[1100px] text-left text-sm">
                                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Category / Decision</th>
                                    <th class="px-4 py-3">Description</th>
                                    <th class="px-4 py-3 text-right">Amount</th>
                                    <th class="px-4 py-3 text-right">GST</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                                @forelse ($this->reviewRows as $row)
                                    @php($direction = ((float) $row->amount >= 0) ? 'income' : 'expense')
                                    @php($gstPreview = $this->gstPreviewForRow($row))
                                    <tr @class(['transition-colors hover:bg-gray-100/80 dark:hover:bg-white/[0.05]','bg-gray-50/70 dark:bg-white/[0.02]' => in_array($row->status, ['excluded_personal', 'duplicate'], true),])>
                                        {{-- Date --}}
                                        <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-200">
                                            {{ $row->transaction_date?->format('d/m/Y') ?? '—' }}
                                        </td>
                                        {{-- Status --}}
                                        <td class="px-4 py-3">
                                            @switch($row->status)
                                                @case('pending')
                                                    @if ($row->suggested_book_category_id)
                                                        <button
                                                            type="button"
                                                            wire:click="useSuggestion({{ $row->id }})"
                                                            class="inline-flex items-center gap-1 rounded-full bg-warning-50 px-2 py-1 text-xs font-semibold text-warning-700 hover:bg-warning-100 dark:bg-warning-500/10 dark:text-warning-300 dark:hover:bg-warning-500/20"
                                                            title="Accept suggested category"
                                                        >
                                                            <x-heroicon-o-check class="h-3.5 w-3.5"/>
                                                            Confirm
                                                        </button>
                                                    @else
                                                        <span class="inline-flex rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">Review</span>
                                                    @endif
                                                    @break
                                                @case('ready')
                                                    <span class="inline-flex rounded-full bg-success-50 px-2 py-1 text-xs font-semibold text-success-700 dark:bg-success-500/10 dark:text-success-300">Ready</span>
                                                    @break
                                                @case('excluded_personal')
                                                    <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300">Personal</span>
                                                    @break
                                                @case('duplicate')
                                                    <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300">Duplicate</span>
                                                    @break
                                                @case('invalid')
                                                    <span class="inline-flex rounded-full bg-danger-50 px-2 py-1 text-xs font-medium text-danger-700 dark:bg-danger-500/10 dark:text-danger-300">Invalid</span>
                                                    @break
                                                @case('included')
                                                    <span class="inline-flex rounded-full bg-success-50 px-2 py-1 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-300">Imported</span>
                                                    @break
                                            @endswitch
                                        </td>
                                        {{-- Category --}}
                                        <td class="w-[300px] px-4 py-3">
                                            @if (in_array($row->status, ['pending', 'ready'], true))
                                                <x-books.select wire:key="import-row-category-{{ $row->id }}-{{ $row->status }}-{{ $row->book_category_id ?? $row->suggested_book_category_id ?? 'none' }}"
                                                                :value="$row->book_category_id ?: $row->suggested_book_category_id" :options="$this->categories->whereIn('type', [$direction, 'other'])->pluck('name', 'id')->all()"
                                                                action="selectCategory" :action-args="[$row->id]" placeholder="Choose category…" allow-empty empty-label="Choose category…"/>

                                                @if ($row->book_category_id)
                                                    @php($hasPartialBusinessUse = (float) ($row->business_use_percentage ?? 100) !== 100.0)
                                                    @php($isCapitalPurchase = (float) $row->amount < 0 && $row->purchase_type === 'capital')

                                                    @if ($hasPartialBusinessUse || $isCapitalPurchase)
                                                        <div class="mt-1.5 text-xs font-medium text-warning-700 dark:text-warning-300">
                                                            @if ($hasPartialBusinessUse)
                                                                {{ number_format((float) ($row->business_use_percentage ?? 100), 0) }}% business use
                                                            @endif

                                                            @if ($hasPartialBusinessUse && $isCapitalPurchase)
                                                                ·
                                                            @endif

                                                            @if ($isCapitalPurchase)
                                                                Capital
                                                            @endif
                                                        </div>
                                                    @endif
                                                @elseif ($row->suggestedCategory)
                                                    @php($suggestionSource = ($row->raw_data ?? [])['suggestion_source'] ?? null)
                                                    @php($linkedParent = ($row->raw_data ?? [])['linked_parent_description'] ?? null)

                                                    <div class="mt-1.5 text-xs font-medium text-[#e87000]">
                                                        @if ($suggestionSource === 'linked_international_fee' && $linkedParent)
                                                            {{ $row->suggestion_confidence }}% linked to {{ \Illuminate\Support\Str::limit($linkedParent, 34) }} · click Confirm
                                                        @else
                                                            {{ $row->suggestion_confidence }}% suggestion · click Confirm to accept
                                                        @endif
                                                    </div>
                                                @endif
                                            @elseif ($row->status === 'excluded_personal')
                                                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Personal — excluded from Books</span>
                                            @elseif ($row->status === 'duplicate')
                                                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Already seen — not imported again</span>
                                            @elseif ($row->status === 'included')
                                                <span class="text-sm font-medium text-gray-950 dark:text-white">{{ $row->category?->name ?? '—' }}</span>
                                            @else
                                                <span class="text-sm text-gray-500 dark:text-gray-400">Needs correction</span>
                                            @endif
                                        </td>
                                        {{-- Description --}}
                                        <td class="max-w-xl px-4 py-3">
                                            <div class="font-medium text-gray-950 dark:text-white">{{ $row->description }}</div>
                                            @if ($row->status === 'invalid')
                                                <div class="mt-1 text-xs font-medium text-danger-600 dark:text-danger-400">{{ implode(', ', $row->raw_data['errors'] ?? ['Invalid row']) }}</div>
                                            @endif
                                        </td>
                                        {{-- Amount --}}
                                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold {{ (float) $row->amount >= 0 ? 'text-success-600 dark:text-success-400' : 'text-gray-950 dark:text-white' }}">
                                            @if ($row->amount !== null)
                                                {{ (float) $row->amount >= 0 ? '+' : '-' }}${{ number_format(abs((float) $row->amount), 2) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        {{-- Gst --}}
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-gray-500 dark:text-gray-400">
                                            @if (! $gstPreview['known'])
                                                ?
                                            @elseif ($gstPreview['amount'] > 0)
                                                {{ $gstPreview['estimated'] ? '~' : '' }}${{ number_format($gstPreview['amount'], 2) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="ml-auto grid w-[6.75rem] grid-cols-3 place-items-center gap-1">
                                                @if (in_array($row->status, ['pending', 'ready'], true))
                                                    <button type="button" wire:click="editImportRow({{ $row->id }})" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-gray-200" title="Adjust transaction">
                                                        <x-heroicon-o-adjustments-horizontal class="h-4 w-4"/>
                                                    </button>
                                                    <button type="button" wire:click="markPersonal({{ $row->id }})" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-gray-200" title="Mark personal">
                                                        <x-heroicon-o-user-minus class="h-4 w-4"/>
                                                    </button>
                                                    @if ($row->status === 'ready')
                                                        <button type="button" wire:click="resetDecision({{ $row->id }})" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-gray-200" title="Reset decision">
                                                            <x-heroicon-o-arrow-uturn-left class="h-4 w-4"/>
                                                        </button>
                                                    @else
                                                        <span class="h-8 w-8" aria-hidden="true"></span>
                                                    @endif
                                                @elseif ($row->status === 'excluded_personal')
                                                    <span class="h-8 w-8" aria-hidden="true"></span>
                                                    <span class="h-8 w-8" aria-hidden="true"></span>
                                                    <button type="button" wire:click="resetDecision({{ $row->id }})" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-gray-200" title="Undo personal">
                                                        <x-heroicon-o-arrow-uturn-left class="h-4 w-4"/>
                                                    </button>
                                                @elseif ($row->status === 'duplicate' && $import->status === 'review')
                                                    <button type="button" wire:click="treatDuplicateAsNew({{ $row->id }})" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-gray-200" title="Treat duplicate as new">
                                                        <x-heroicon-o-arrow-path class="h-4 w-4"/>
                                                    </button>
                                                    <span class="h-8 w-8" aria-hidden="true"></span>
                                                    <span class="h-8 w-8" aria-hidden="true"></span>
                                                @else
                                                    <span class="h-8 w-8" aria-hidden="true"></span>
                                                    <span class="h-8 w-8" aria-hidden="true"></span>
                                                    <span class="h-8 w-8" aria-hidden="true"></span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">No rows match this filter.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($this->reviewRows)
                        <div class="mt-4">{{ $this->reviewRows->links() }}</div>
                    @endif

                    @if ($import->status === 'review')
                        <div class="mt-6 flex flex-col gap-4 border-t border-gray-200 pt-5 dark:border-white/10 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                @if ($stats['pending'] > 0)
                                    <div class="font-medium text-warning-700 dark:text-warning-300">{{ $stats['pending'] }} {{ \Illuminate\Support\Str::plural('transaction', $stats['pending']) }} still need a decision.</div>
                                @elseif ($stats['invalid'] > 0)
                                    <div class="font-medium text-danger-700 dark:text-danger-300">{{ $stats['invalid'] }} invalid {{ \Illuminate\Support\Str::plural('row', $stats['invalid']) }} must be resolved before importing.</div>
                                @else
                                    <div class="font-medium text-success-700 dark:text-success-300">Review complete. {{ $stats['ready'] }} business transactions will be added.</div>
                                @endif
                                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $stats['personal'] }} personal · {{ $stats['duplicates'] }} duplicate</div>
                            </div>

                            <x-filament::button
                                wire:click="finalizeImport"
                                wire:loading.attr="disabled"
                                wire:target="finalizeImport"
                                icon="heroicon-o-check-circle"
                                :disabled="! $this->canFinalize"
                            >
                                Complete Import
                            </x-filament::button>
                        </div>
                    @else
                        <div class="mt-6 flex justify-end border-t border-gray-200 pt-5 dark:border-white/10">
                            <a href="{{ \App\Filament\Pages\Books\BooksTransactions::getUrl() }}" class="inline-flex items-center gap-2 text-sm font-semibold text-primary-600 hover:underline dark:text-primary-400">View Books Transactions →</a>
                        </div>
                    @endif
                </x-filament::card>
            </div>
        @endif
    @endif

    @if ($this->recentImports->isNotEmpty())
        <x-filament::card class="mt-6">
            <div class="mb-4">
                <div class="text-base font-semibold text-gray-950 dark:text-white">Recent imports</div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Open a draft to continue reviewing it, or inspect the result of a completed import.</p>
            </div>

            <div class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($this->recentImports as $recent)
                    <button type="button" wire:click="openImport({{ $recent->id }})" class="flex w-full flex-col gap-2 py-3 text-left first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="font-medium text-gray-950 dark:text-white">{{ $recent->original_filename ?? 'Bank import #' . $recent->id }}</div>
                            <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                {{ $recent->bankAccount?->name ?? 'Bank account' }}
                                @if ($recent->period_start && $recent->period_end)
                                    · {{ $recent->period_start->format('j M') }} – {{ $recent->period_end->format('j M Y') }}
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <span class="text-gray-500 dark:text-gray-400">{{ $recent->row_count }} rows</span>
                            @if ($recent->status === 'complete')
                                <span class="rounded-full bg-success-50 px-2 py-1 font-medium text-success-700 dark:bg-success-500/10 dark:text-success-300">{{ $recent->included_count }} imported</span>
                            @else
                                <span class="rounded-full bg-warning-50 px-2 py-1 font-medium text-warning-700 dark:bg-warning-500/10 dark:text-warning-300">Continue review</span>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>
        </x-filament::card>
    @endif

    <x-filament::modal id="bookImportHelp" width="2xl">
        <x-slot name="heading">How bank imports work</x-slot>
        <x-slot name="description">The bank file is only a source. Nothing becomes a Books transaction until you complete the review.</x-slot>

        <div class="space-y-4 text-sm text-gray-600 dark:text-gray-300">
            <div class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-50 font-semibold text-primary-600 dark:bg-primary-500/10 dark:text-primary-300">1</span>
                <div><span class="font-semibold text-gray-950 dark:text-white">Choose the bank CSV.</span> Books remembers the column mapping separately for each bank account.</div>
            </div>
            <div class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-50 font-semibold text-primary-600 dark:bg-primary-500/10 dark:text-primary-300">2</span>
                <div><span class="font-semibold text-gray-950 dark:text-white">Review the staged rows.</span> Duplicate rows are detected automatically, including transactions previously marked Personal.</div>
            </div>
            <div class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-50 font-semibold text-primary-600 dark:bg-primary-500/10 dark:text-primary-300">3</span>
                <div><span class="font-semibold text-gray-950 dark:text-white">Make a decision.</span> Confirm a category, adjust GST/business use if needed, or mark accidental personal spending as Personal.</div>
            </div>
            <div class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-50 font-semibold text-primary-600 dark:bg-primary-500/10 dark:text-primary-300">4</span>
                <div><span class="font-semibold text-gray-950 dark:text-white">Complete the import.</span> Only then are the approved business rows created as real Books transactions used by BAS and reports.</div>
            </div>
        </div>

        <div class="mt-6 flex justify-end border-t border-gray-200 pt-5 dark:border-white/10">
            <x-filament::button x-on:click="$dispatch('close-modal', { id: 'bookImportHelp' })">Got it</x-filament::button>
        </div>
    </x-filament::modal>

    <x-filament::modal id="bookDiscardImportConfirm" width="lg">
        <x-slot name="heading">Discard this import?</x-slot>
        <x-slot name="description">This removes the staged import and its bank rows. No completed Books transactions will be touched.</x-slot>

        @if ($this->discardingImport)
            <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                <div class="font-medium text-gray-950 dark:text-white">{{ $this->discardingImport->original_filename ?? 'Bank import' }}</div>
                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $this->discardingImport->row_count }} {{ \Illuminate\Support\Str::plural('staged row', $this->discardingImport->row_count) }} will be removed.
                </div>
            </div>
        @endif

        <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-5 dark:border-white/10">
            <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'bookDiscardImportConfirm' })">Cancel</x-filament::button>
            <x-filament::button color="danger" icon="heroicon-o-trash" wire:click="discardImport" wire:loading.attr="disabled" wire:target="discardImport">
                Discard Import
            </x-filament::button>
        </div>
    </x-filament::modal>

    <x-filament::modal id="bookImportRowEditor" width="xl" slide-over>
        <x-slot name="heading">Adjust Imported Transaction</x-slot>
        <x-slot name="description">Override the category defaults for this transaction only. The original bank row is still retained unchanged.</x-slot>

        @if ($editingImportRowId)
            @php($editingRow = $this->editingRow)
            @if ($editingRow)
                <div class="mb-5 rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Bank transaction</div>
                    <div class="mt-1 font-medium text-gray-950 dark:text-white">{{ $editingRow->description }}</div>
                    <div class="mt-2 flex items-center justify-between gap-3 text-sm">
                        <span class="text-gray-500 dark:text-gray-400">{{ $editingRow->transaction_date?->format('d/m/Y') }}</span>
                        <span class="font-semibold {{ (float) $editingRow->amount >= 0 ? 'text-success-600 dark:text-success-400' : 'text-gray-950 dark:text-white' }}">
                            {{ (float) $editingRow->amount >= 0 ? '+' : '-' }}${{ number_format(abs((float) $editingRow->amount), 2) }}
                        </span>
                    </div>
                </div>

                @php($editingDirection = ((float) $editingRow->amount >= 0) ? 'income' : 'expense')

                <div class="space-y-5">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Category</label>
                        <x-books.select
                            model="rowCategoryId"
                            :value="$rowCategoryId"
                            :options="$this->categories->whereIn('type', [$editingDirection, 'other'])->pluck('name', 'id')->all()"
                            placeholder="Choose category…"
                            allow-empty
                            empty-label="Choose category…"
                        />
                        @error('rowCategoryId')
                        <div class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</div> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Business use</label>
                            <x-filament::input.wrapper>
                                <x-slot name="suffix">%</x-slot>
                                <x-filament::input type="number" min="0" max="100" step="1" wire:model.live.debounce.300ms="rowBusinessUsePercentage"/>
                            </x-filament::input.wrapper>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">GST</label>
                            @if (! $this->gstRegistered)
                                <div class="flex min-h-10 items-center rounded-lg bg-gray-50 px-3 text-sm text-gray-500 ring-1 ring-gray-950/10 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10">
                                    Business is not GST registered
                                </div>
                            @elseif ($rowGstTreatment === 'excluded')
                                <div class="flex min-h-10 items-center rounded-lg bg-gray-50 px-3 text-sm text-gray-500 ring-1 ring-gray-950/10 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10">
                                    Excluded from BAS by category
                                </div>
                            @else
                                <label class="flex min-h-10 cursor-pointer items-center gap-2 rounded-lg border border-gray-200 px-3 text-sm text-gray-700 dark:border-white/10 dark:text-gray-200">
                                    <input
                                        type="checkbox"
                                        wire:model.live="rowIncludesGst"
                                        class="rounded border-gray-300 !text-[#e87000] focus:!ring-[#e87000] dark:border-white/20 dark:bg-white/10"
                                        style="accent-color: #e87000;"
                                    >
                                    <span class="font-medium">Includes GST</span>
                                </label>
                            @endif
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-2">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">GST amount</label>
                                @if ($this->gstRegistered && $rowIncludesGst && $rowGstTreatment !== 'excluded')
                                    <button type="button" wire:click="recalculateRowGstAmount" class="text-xs font-semibold text-[#e87000] hover:underline">Auto</button>
                                @endif
                            </div>
                            <x-filament::input.wrapper>
                                <x-slot name="prefix">$</x-slot>
                                <x-filament::input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    wire:model.live.debounce.300ms="rowGstAmountInput"
                                    :disabled="! $this->gstRegistered || ! $rowIncludesGst || $rowGstTreatment === 'excluded'"
                                />
                            </x-filament::input.wrapper>
                            @error('rowGstAmountInput')
                            <div class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</div> @enderror
                        </div>

                        @if ($editingDirection === 'expense')
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Purchase type</label>
                                <x-books.select
                                    model="rowPurchaseType"
                                    :value="$rowPurchaseType"
                                    :options="['non_capital' => 'Non-capital', 'capital' => 'Capital']"
                                />
                            </div>
                        @endif
                    </div>

                    @if (! app(\App\Services\Books\BankCsvImportService::class)->isInternationalTransactionFee((string) $editingRow->description))
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-3 dark:border-white/10">
                            <input
                                type="checkbox"
                                wire:model="rowRememberMerchant"
                                class="mt-0.5 rounded border-gray-300 !text-[#e87000] focus:!ring-[#e87000] dark:border-white/20 dark:bg-white/10"
                                style="accent-color: #e87000;"
                            >
                            <span>
                                <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">Remember this merchant</span>
                                <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">Create a category rule for future matching bank descriptions. Use this when the merchant should consistently use this category.</span>
                            </span>
                        </label>
                    @else
                        <div class="rounded-xl border border-gray-200 p-3 text-xs text-gray-500 dark:border-white/10 dark:text-gray-400">
                            International Transaction Fee uses the category of its linked overseas purchase rather than a single permanent merchant rule.
                        </div>
                    @endif

                    <div class="flex justify-end gap-3 border-t border-gray-200 pt-5 dark:border-white/10">
                        <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'bookImportRowEditor' })">Cancel</x-filament::button>
                        <x-filament::button wire:click="saveImportRowAdjustments" icon="heroicon-o-check">Confirm Transaction</x-filament::button>
                    </div>
                </div>
            @endif
        @endif
    </x-filament::modal>

    <x-filament::modal id="bookBankAccountQuickCreate" width="lg">
        <x-slot name="heading">Add Bank Account</x-slot>
        <x-slot name="description">Books will remember this account's CSV mapping after the first successful upload.</x-slot>

        <div class="space-y-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Name</label>
                <x-filament::input.wrapper>
                    <x-filament::input wire:model="newBankName" placeholder="e.g. CBA Business Account"/>
                </x-filament::input.wrapper>
                @error('newBankName')
                <div class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Institution</label>
                <x-filament::input.wrapper>
                    <x-filament::input wire:model="newBankInstitution" placeholder="Commonwealth Bank"/>
                </x-filament::input.wrapper>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">Last 4 digits <span class="font-normal text-gray-400">(optional)</span></label>
                <x-filament::input.wrapper>
                    <x-filament::input wire:model="newBankLastFour" maxlength="4" inputmode="numeric" placeholder="1561"/>
                </x-filament::input.wrapper>
                @error('newBankLastFour')
                <div class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</div> @enderror
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 pt-5 dark:border-white/10">
                <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'bookBankAccountQuickCreate' })">Cancel</x-filament::button>
                <x-filament::button wire:click="createBankAccount" icon="heroicon-o-check">Add Account</x-filament::button>
            </div>
        </div>
    </x-filament::modal>
</x-filament-panels::page>
