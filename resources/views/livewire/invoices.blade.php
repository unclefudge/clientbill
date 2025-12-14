<div>
    <style>
        .hoverPrimary:hover {
            color: rgb(245, 158, 11)
        }

        .hoverDanger:hover {
            color: rgb(239, 68, 68)
        }

        html, body {
            margin: 0;
            padding: 0;
            overflow: hidden !important; /* disable browser scroll */
            height: 100% !important;
        }
    </style>


    {{-- Header button + Title --}}
    <div>
        <div class="flex justify-between mb-4">
            <div class="flex gap-2 w-20">
                <a class="{{ $view === 'list' ? 'text-gray-300' : 'text-gray-700 hover:text-primary-500 hover:cursor-pointer' }}" wire:click.stop="changeView('list')">
                    <x-heroicon-s-queue-list class="w-10 h-10"/>
                </a>
            </div>
            <div class="flex-grow gap-6">
                <h1 class="font-bold text-3xl hover:text-primary-500 hover:cursor-pointer">Invoices</h1>
            </div>
            <div class="flex gap-2 justify-end">
                @if ($view == 'list')
                    <x-filament::button wire:click.stop="openCreateInvoiceModal()" size="sm">Create Invoice</x-filament::button>
                @else
                    @if ($activeInvoice && !$activeInvoice->paid_date)
                        <x-filament::button wire:click.stop="markInvoiceSent()" size="sm" color="gray" class="mr-2">
                            <x-heroicon-o-envelope class="w-6 h-6"/>
                            Mark Sent
                        </x-filament::button>
                    @endif
                    <x-filament::button wire:click.stop="printInvoice()" size="sm">
                        <x-heroicon-o-printer class="w-6 h-6"/>
                        Print
                    </x-filament::button>
                    <div x-data x-on:open-print-window.window="window.open($event.detail.url, '_blank')"></div>

                    <x-filament::button size="sm" tag="a" href="{{ route('invoice.pdf', ['invoice' => $activeInvoice->id]) }}" target="_blank">
                        <x-heroicon-o-arrow-down-tray class="w-6 h-6"/>
                        Download
                    </x-filament::button>

                    <x-filament::button wire:click.stop="confirmDeleteInvoice({{$activeInvoice->id}})" size="sm" color="danger">
                        <x-heroicon-o-trash class="w-6 h-6"/>
                    </x-filament::button>
                @endif
            </div>
        </div>
    </div>

    {{-- BODY VIEW --}}
    <div>
        @if ($view === 'list')
            @include('livewire._inv-list')
        @elseif ($view === 'single' && $activeInvoice)
            @include('livewire._inv-single')
        @endif
    </div>

    <div class="hidden bg-primary-500 bg-green-600 bg-primary-50 bg-red-50 dark:bg-red-900 dark:bg-primary-900 dark:bg-primary-800"></div>
    <div class="hidden bg-red-50 dark:bg-red-900/30 bg-green-50 dark:bg-green-900/30 bg-blue-50 dark:bg-blue-900/30 bg-yellow-50 dark:bg-yellow-900/30 bg-purple-50 dark:bg-purple-900/30
bg-orange-50 dark:bg-orange-900/30 bg-pink-50 dark:bg-pink-900/30 dark:bg-gray-800/40 dark:text-white dark:text-gray-300"></div>
    <div
        class="hidden text-green-600 dark:text-green-300 bg-green-500/10 dark:bg-green-400/10 border border-green-500/20 dark:border-green-400/20 text-red-500 dark:text-red-300 bg-red-500/10 dark:bg-red-400/10 border border-red-500/20 dark:border-red-400/20 text-yellow-600 dark:text-yellow-300 bg-yellow-500/10 dark:bg-yellow-400/10 border border-yellow-500/20 dark:border-yellow-400/20"></div>
    {{-- Modals --}}
    @include('livewire._inv-modals')
    <x-filament-actions::modals/>
</div>
