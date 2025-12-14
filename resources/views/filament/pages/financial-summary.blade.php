<x-filament::page>

    {{-- CONTROLS --}}
    <div class="flex flex-wrap gap-3 mb-6 items-center">

        {{-- Sort --}}
        <div class="flex gap-2">
            <button wire:click="setSort('year')" class="px-3 py-1 rounded-md text-sm font-semibold
                {{ $sortBy === 'year' ? 'bg-primary-600 text-white' : 'bg-gray-200 dark:bg-gray-700' }}">
                Sort by Year
            </button>

            <button wire:click="setSort('total')" class="px-3 py-1 rounded-md text-sm font-semibold
                {{ $sortBy === 'total' ? 'bg-primary-600 text-white' : 'bg-gray-200 dark:bg-gray-700' }}">
                Sort by Total
            </button>

            <x-filament::input.wrapper>
                <button
                    wire:click="toggleHoursOnly"
                    type="button"
                    class="
            px-3 py-1.5 rounded-md text-sm font-semibold transition
            border
            {{ $hoursOnly
                ? 'bg-primary-600 border-primary-600 text-white hover:bg-primary-500'
                : 'bg-gray-200 border-gray-300 text-gray-700 hover:bg-gray-300
                   dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600'
            }}
        "
                >
                    {{ $hoursOnly ? 'Hours only' : 'Everything' }}
                </button>
            </x-filament::input.wrapper>
        </div>

        {{-- Client Filter --}}
        <div>
            <x-filament::input.wrapper class="w-64">
                <x-filament::input.select wire:model.live="clientId">
                    <option value="">All Clients</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client['id'] }}">{{ $client['name'] }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>

    </div>

    {{-- FINANCIAL YEARS --}}
    <div class="space-y-4 max-w-4xl">

        @foreach ($financialYears as $year => $data)
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">

                {{-- HEADER --}}
                <button wire:click="toggleYear('{{ $year }}')" class="w-full px-5 py-4 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition">

                    <div class="flex justify-between items-center">
                        <div class="font-bold text-lg">{{ $year }}</div>

                        <div class="flex items-center gap-4">
                            {{-- % Change --}}
                            @if ($data['change'] !== null)
                                <span class="text-sm font-semibold
                                    {{ $data['change'] >= 0 ? 'text-green-600' : 'text-red-500' }}">
                                    {{ $data['change'] >= 0 ? '+' : '' }}{{ $data['change'] }}%
                                </span>
                            @endif

                            <div class="font-bold text-lg">
                                ${{ number_format($data['total'], 2) }}
                            </div>
                        </div>
                    </div>

                    {{-- MINI BAR --}}
                    <div class="mt-2 h-2 bg-gray-200 dark:bg-gray-700 rounded">
                        <div class="h-2 bg-primary-800 rounded"
                             style="width: {{ ($data['total'] / $maxTotal) * 100 }}%">
                        </div>
                    </div>
                </button>

                {{-- EXPANDED --}}
                @if (in_array($year, $expandedYears, true))
                    <div class="px-5 py-4 bg-white dark:bg-gray-900 space-y-2">

                        <div class="flex justify-between">
                            <span>Hours</span>
                            <span>${{ number_format($data['hours'], 2) }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span>Domains</span>
                            <span>${{ number_format($data['domains'], 2) }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span>Hosting</span>
                            <span>${{ number_format($data['hosting'], 2) }}</span>
                        </div>

                        <div class="flex justify-between font-semibold border-t pt-2">
                            <span>Additional</span>
                            <span>${{ number_format($data['additional'], 2) }}</span>
                        </div>

                    </div>
                @endif

            </div>
        @endforeach

        @if (empty($financialYears))
            <div class="text-gray-500">No financial data available.</div>
        @endif
    </div>

</x-filament::page>
