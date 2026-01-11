<div x-data="{ dateSelect: false }">
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

    {{-- Month Picker --}}
    <div x-show="dateSelect"
         class="flex justify-between h-64 w-full bg-gray-100 dark:bg-gray-800"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-90"
    >
        <div class="grow" x-on:click="dateSelect = false"></div>

        <div class="flex w-16 text-9xl items-center justify-center">
            <div class="hover:text-primary-500 hover:cursor-pointer" wire:click="changeYear('prev')">
                &#8249;
            </div>
        </div>

        <div class="w-96">
            <div class="text-center pt-3 mb-5">
                <h1 class="font-bold text-3xl hover:cursor-pointer" x-on:click="dateSelect = false">
                    {{ $selectYear }}
                    <x-heroicon-o-calendar-days class="w-7 h-7 ml-2 -mt-2 text-gray-500 inline"/>
                </h1>
            </div>

            <div class="grid grid-cols-3 gap-2 w-full">
                @foreach ([
                    '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
                    '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug',
                    '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'
                ] as $mNum => $mName)
                    <div class="text-center text-xl hover:text-primary-500 hover:cursor-pointer" wire:click="changeMonth('{{ $selectYear }}-{{ $mNum }}-01')" x-on:click="dateSelect = false">
                        {{ $mName }} {{ $selectYear }}
                    </div>
                @endforeach
            </div>

            <div class="text-center pt-2">
                <h1 class="font-bold text-xl hover:text-primary-500 hover:cursor-pointer" wire:click="changeMonth('today')" x-on:click="dateSelect = false">
                    TODAY
                </h1>
            </div>
        </div>

        <div class="flex w-16 text-9xl items-center justify-center">
            <div class="hover:text-primary-500 hover:cursor-pointer" wire:click="changeYear('next')">
                &#8250;
            </div>
        </div>

        <div class="flex grow justify-end" x-on:click="dateSelect = false"></div>
    </div>

    {{-- Header button + Title --}}
    <div x-show="!dateSelect">
        <div class="flex mb-4">
            <div class="flex gap-2 w-40">
                <a class="{{ $view === 'month' ? 'text-gray-300' : 'text-gray-700 hover:text-primary-500 hover:cursor-pointer' }}" wire:click.stop="changeView('month')">
                    <x-heroicon-s-calendar-days class="w-10 h-10"/>
                </a>
                <a class="{{ $view === 'list' ? 'text-gray-300' : 'text-gray-700 hover:text-primary-500 hover:cursor-pointer' }}" wire:click.stop="changeView('list')">
                    <x-heroicon-s-queue-list class="w-10 h-10"/>
                </a>
                {{--}}<a class="{{ $view === 'bar' ? 'text-gray-700' : 'text-gray-300 hover:text-primary-500 hover:cursor-pointer' }}" wire:click.stop="changeView('bar')">
                    <x-heroicon-s-view-columns class="w-10 h-10"/>
                </a>--}}
            </div>
            <div class="flex gap-6">
                <div class="text-4xl text-gray-800 dark:text-gray-300 dark:hover:text-primary-500 font-black hover:text-primary-500 hover:cursor-pointer" wire:click="changeMonth('prev')">
                    &lt;
                </div>

                <div x-on:click="dateSelect = true">
                    <h1 class="font-bold text-3xl hover:text-primary-500 hover:cursor-pointer">{{ $currentMonth->format('M, Y') }}</h1>
                </div>

                <div class="text-4xl text-gray-800 dark:text-gray-300 dark:hover:text-primary-500 font-black hover:text-primary-500 hover:cursor-pointer" wire:click="changeMonth('next')">
                    &gt;
                </div>
            </div>
        </div>
    </div>

    {{-- BODY VIEW --}}
    <div>
        @if ($view === 'month')
            @include('livewire._cal-month')
        @elseif ($view === 'list')
            @include('livewire._cal-list')
        @endif
    </div>

    <div class="hidden bg-primary-500 bg-green-600 bg-primary-50 bg-red-50 dark:bg-red-900 dark:bg-primary-900 dark:bg-primary-800"></div>
    <div class="hidden bg-red-50 dark:bg-red-900/30 bg-green-50 dark:bg-green-900/30 bg-blue-50 dark:bg-blue-900/30 bg-yellow-50 dark:bg-yellow-900/30 bg-purple-50 dark:bg-purple-900/30
bg-orange-50 dark:bg-orange-900/30 bg-pink-50 dark:bg-pink-900/30 dark:bg-gray-800/40 dark:text-white dark:text-gray-300"></div>
    <div class="hidden text-green-600 dark:text-green-300 bg-green-500/10 dark:bg-green-400/10 border border-green-500/20 dark:border-green-400/20 text-red-500 dark:text-red-300 bg-red-500/10 dark:bg-red-400/10 border border-red-500/20 dark:border-red-400/20 text-yellow-600 dark:text-yellow-300 bg-yellow-500/10 dark:bg-yellow-400/10 border border-yellow-500/20 dark:border-yellow-400/20"></div>
    {{-- Modals --}}
    @include('livewire._cal-modals')
    <x-filament-actions::modals/>
</div>
