<x-filament-panels::page>

    {{-- =============================== --}}
    {{-- TOP SUMMARY CARDS --}}
    {{-- =============================== --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">

        {{-- Hours This Month --}}
        <x-filament::card wire:click="viewUnbilledHours" class="hover:cursor-pointer transition hover:-translate-y-0.5 hover:border-emerald-400/50">
            <div class="flex flex-col justify-between h-full">

                {{-- Icon + Text --}}
                <div class="flex items-start gap-4">
                    <x-heroicon-o-clock class="w-10 h-10 text-primary-500"/>
                    <div class="flex flex-col">
                        <div class="text-sm font-semibold text-gray-400 dark:text-gray-200">Hours This Month</div>
                        <div class="text-3xl font-bold text-gray-600 dark:text-white leading-tight">{{ $hoursThisMonth ?? 0 }}</div>
                    </div>
                </div>

                {{-- Bottom Line --}}
                <div class="mt-4 flex items-center text-xs text-primary-500">
                    <x-heroicon-o-chevron-up class="w-3 h-3 mr-1"/>
                    <span>Unbilled: {{ $unbilledHours ?? 0 }}h</span>
                </div>

            </div>
        </x-filament::card>


        {{-- Upcoming Renewals --}}
        <x-filament::card wire:click="viewRenewals" class="hover:cursor-pointer transition hover:-translate-y-0.5 hover:border-yellow-400/50">
            <div class="flex flex-col justify-between h-full">

                <div class="flex items-start gap-4">
                    <x-heroicon-o-arrow-path class="w-10 h-10 text-yellow-400"/>
                    <div class="flex flex-col">
                        <div class="text-sm font-semibold text-gray-400 dark:text-gray-200">Renewals ({{ $upcomingDays }} Days)</div>
                        <div class="text-3xl font-bold text-gray-600 dark:text-white leading-tight">{{ $renewalsCount ?? 0 }}</div>
                    </div>
                </div>

                <div class="mt-4 flex items-center text-xs text-yellow-400">
                    <x-heroicon-o-chevron-up class="w-3 h-3 mr-1"/>
                    <span>Renewals due soon</span>
                </div>

            </div>
        </x-filament::card>

        {{-- Unpaid Invoices Widget --}}
        <x-filament::card class="transition hover:-translate-y-0.5 hover:border-sky-400/50">
            <div class="flex flex-col justify-between h-full">

                <div class="flex items-start gap-4">
                    <x-heroicon-o-credit-card class="w-10 h-10 {{ ($unpaidCount > 0) ? 'text-red-400' : 'text-emerald-400'  }}"/>
                    <div class="flex flex-col">
                        <div class="text-sm font-semibold text-gray-400 dark:text-gray-200">Unpaid Invoices</div>
                        <div class="text-3xl font-bold text-gray-600 dark:text-white leading-tight">{{ $unpaidCount ?? 0 }}</div>
                    </div>
                </div>

                <div class="mt-4 flex items-center text-xs text-red-400">
                    <x-heroicon-o-chevron-up class="w-3 h-3 mr-1 {{ ($overdueCount > 0) ? 'text-red-400' : 'text-emerald-400'  }}"/>
                    <span class="{{ ($overdueCount > 0) ? 'text-red-400' : 'text-emerald-400'  }}">{{ $overdueCount ?? 0 }} overdue invoices</span>
                </div>

            </div>
        </x-filament::card>
    </div>


    {{-- =============================== --}}
    {{-- CLIENT SUGGESTION SECTIONS --}}
    {{-- =============================== --}}
    <div class="space-y-4 mt-4">

        @foreach ($suggestions as $client)

            {{-- Skip if nothing to do --}}
            @php
                $hide =
                    ($client['unbilled_hours'] == 0) &&
                    ($client['renewals'] == 0) &&
                    ((int)$client['hour_balance'] === 0) &&
                    (!$client['can_invoice']);

                // Progress bar hours
                $value = $client['unbilled_hours'] ?? 0;
                $max = 50;
                $percent = ($value / $max) * 100;
                $color =$percent < 50 ? 'bg-green-500' : ($percent < 80 ? 'bg-amber-500' : 'bg-red-500');
            @endphp

            @continue($hide)

            {{-- Compact client card --}}
            <x-filament::card>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                    {{-- Client Name --}}
                    <div>
                        <div class="text-md font-bold">{{ $client['client_name'] }}</div>
                        @if ($client['hour_balance_label'] != 'Balanced')
                            <div class="text-xs text-primary-500 ">{{ $client['hour_balance_label'] }}</div>
                        @endif
                    </div>

                    {{-- Hours --}}
                    <div class="hover:cursor-pointer hover:text-primary-500" wire:click="viewUnbilledHours({{ $client['client_id'] }})">
                        <div class="text-xs text-gray-400">Unbilled Hours</div>
                        <div class="text-lg font-semibold hover:cursor-pointer">
                            {{ $client['unbilled_hours'] }}h
                        </div>
                    </div>

                    {{-- Renewals --}}
                    <div class="hover:cursor-pointer  hover:text-primary-500" wire:click="viewRenewals({{ $client['client_id'] }})">
                        <div class="text-xs text-gray-400">Renewals</div>
                        <div class="text-lg font-semibold">
                            {{ $client['renewals'] }}
                        </div>
                    </div>

                    {{-- Suggested Action --}}
                    <div class="flex justify-end items-center">
                        @if ($client['can_invoice'])
                            <div class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-md text-primary-600 dark:text-primary-300 bg-primary-500/10
                            dark:bg-primary-400/10 border border-primary-500/20 dark:border-primary-400/20 hover:cursor-pointer hover:text-primary-500 hover:bg-primary-500/10"
                                 wire:click="openCreateInvoiceModal({{ $client['client_id'] }})">{{ $client['button'] }}
                            </div>
                        @else
                            <x-filament::badge color="gray">
                                {{ $client['suggestion'] }}
                            </x-filament::badge>
                        @endif
                    </div>

                </div>

                {{-- Progress bar --}}
                @if ($client['client_name'] == 'Cape Cod')
                    <div class="w-full mt-2 bg-gray-700 rounded-full h-3 overflow-hidden">
                        <div class="{{ $color }} h-3 rounded-full transition-all duration-500" style="width: {{ $percent }}%;"></div>
                    </div>
                    <div class="mt-1 text-xs text-gray-400">Hours ({{ $value }} / {{ $max }})</div>
                @endif
            </x-filament::card>

        @endforeach

    </div>

    {{-- ===================================================== --}}
    {{-- COLLAPSIBLE: ADDITIONAL CLIENTS OVERVIEW --}}
    {{-- ===================================================== --}}
    <x-filament::section collapsible collapsed heading="Additional Clients">

        <div class="space-y-4 mt-4">

            @foreach ($suggestions as $client)

                {{-- Skip if nothing to do --}}
                @php
                    $hide =
                        ($client['unbilled_hours'] == 0) &&
                        ($client['renewals'] == 0) &&
                        ((int)$client['hour_balance'] === 0) &&
                        (!$client['can_invoice']);
                @endphp

                @continue(!$hide)

                {{-- Compact client card --}}
                <x-filament::card>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                        {{-- Client Name --}}
                        <div>
                            <div class="text-md font-bold">{{ $client['client_name'] }}</div>
                            <div class="text-xs text-gray-500">{{ $client['hour_balance_label'] }}</div>
                        </div>

                        {{-- Hours --}}
                        <div>
                            <div class="text-xs text-gray-500">Unbilled Hours</div>
                            <div class="text-lg font-semibold">{{ $client['unbilled_hours'] }}h</div>
                        </div>

                        {{-- Renewals --}}
                        <div>
                            <div class="text-xs text-gray-500">Renewals</div>
                            <div class="text-lg font-semibold">{{ $client['renewals'] }}</div>
                        </div>

                        {{-- Suggested Action --}}
                        <div class="flex justify-end items-center">
                            @if ($client['can_invoice'])
                                <x-filament::badge color="success">
                                    Ready to Invoice
                                </x-filament::badge>
                            @else
                                <x-filament::badge color="gray">
                                    {{ $client['suggestion'] }}
                                </x-filament::badge>
                            @endif
                        </div>

                    </div>
                </x-filament::card>

            @endforeach

        </div>

    </x-filament::section>

    @include('filament.pages._dashboard-modals')

</x-filament-panels::page>
