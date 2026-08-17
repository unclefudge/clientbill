@if ($businessId)
    <x-filament::card @class([
        'transition hover:-translate-y-0.5',
        'border-danger-400/50 hover:border-danger-400' => $attentionStatus === 'overdue',
        'border-warning-400/50 hover:border-warning-400' => $attentionStatus === 'due_soon',
        'hover:border-primary-400/50' => ! $attentionStatus,
    ])>
        <div class="flex h-full flex-col justify-between">
            <div class="flex items-start gap-4">
                <x-heroicon-o-book-open @class([
                    'h-10 w-10',
                    'text-danger-500' => $attentionStatus === 'overdue',
                    'text-warning-500' => $attentionStatus === 'due_soon',
                    'text-primary-500' => ! $attentionStatus,
                ]) />
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-semibold text-gray-400 dark:text-gray-200">BAS · {{ $businessName }}</div>

                    @if ($gstRegistered)
                        <div class="mt-1 text-xl font-bold text-gray-700 dark:text-white">{{ $periodLabel }}</div>
                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            @if ($attentionStatus)
                                BAS {{ $gstNetWhole >= 0 ? 'payable' : 'refund' }}
                                <span class="font-semibold text-gray-800 dark:text-gray-100">${{ number_format(abs($gstNetWhole)) }}</span>
                            @else
                                Est. {{ $gstNet >= 0 ? 'payable' : 'credit' }}
                                <span class="font-semibold text-gray-800 dark:text-gray-100">${{ number_format(abs($gstNet), 2) }}</span>
                            @endif
                        </div>
                    @else
                        <div class="mt-1 text-xl font-bold text-gray-700 dark:text-white">Books</div>
                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Not GST registered</div>
                    @endif
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3 text-xs">
                <div>
                    @if ($attentionStatus === 'overdue')
                        <span class="font-semibold text-danger-500">{{ $attentionText }}</span>
                    @elseif ($attentionStatus === 'due_soon')
                        <span class="font-semibold text-warning-500">{{ $attentionText }}</span>
                    @else
                        <span class="{{ $needsReview ? 'text-amber-500' : 'text-emerald-500' }}">
                            {{ $needsReview ? $needsReview . ' need review' : 'Up to date' }}
                        </span>
                    @endif
                </div>

                <a href="{{ $booksUrl }}" wire:navigate class="font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400">
                    Open BAS
                </a>
            </div>
        </div>
    </x-filament::card>
@endif
