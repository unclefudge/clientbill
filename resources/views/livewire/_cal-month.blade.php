{{-- Month View --}}
<div class="h-[calc(100vh-180px)] overflow-y-auto w-full max-w-full">

    {{-- WEEKDAY HEADERS --}}
    <div class="
        grid border-t border-b border-gray-300 bg-gray-800 text-gray-100
        dark:bg-gray-900 dark:text-gray-200
        @if($showWeekend ?? false) grid-cols-7 @else grid-cols-5 @endif
    ">
        @php
            $days = $showWeekend
                ? ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']
                : ['Mon','Tue','Wed','Thu','Fri'];
        @endphp

        @foreach($days as $d)
            <div class="py-3 px-4 text-center font-semibold border-r border-gray-700 last:border-r-0">
                {{ $d }}
            </div>
        @endforeach
    </div>


    {{-- WEEKS --}}
    <div class="divide-y divide-gray-200 dark:divide-gray-700">
        @foreach($calendarData as $week)
            <div class="grid w-full
                @if($showWeekend ?? false) grid-cols-7 @else grid-cols-5 @endif
            ">

                @foreach($week as $day)
                    @php
                        // BG colours
                        /*$bg = $info['cellBG']
                            ?? ($isInMonth
                                    ? 'bg-white dark:bg-gray-800'
                                    : 'bg-gray-100 dark:bg-gray-900/40 opacity-50');*/
                    @endphp

                    @php
                        $info      = $day['data'] ?? null;
                        $entries   = $info['entries'] ?? collect();
                        $hosting   = $info['hosting'] ?? collect();
                        $domains   = $info['domains'] ?? collect();
                        $hours     = $info['hours'] ?? 0;
                        $isInMonth = $day['inMonth'];

                        // TEST whether cellBG is actually set AND non-empty
                        $hasCellBG = !empty($info['cellBG']);

                        if ($hasCellBG)
                            $bg = $info['cellBG']; // Highlight today
                        else
                            $bg = $isInMonth ? 'bg-gray-100/60 dark:bg-gray-900/40' : 'opacity-20'; // Normal days
                    @endphp


                    {{-- DAY CELL --}}
                    <div class="min-h-[150px] border-r last:border-r-0 border-gray-200 dark:border-gray-700 p-2 relative {{ $bg }}">

                        {{-- CLICKABLE FULL CELL --}}
                        <div wire:click.stop="openAddEntryModal('{{ $day['date'] }}')" class="absolute inset-0 z-0 hover:cursor-pointer"></div>

                        {{-- DAY NUMBER --}}
                        <div class="text-sm font-bold mb-1
                            {{ $isInMonth ? 'text-gray-800 dark:text-gray-100' : 'text-gray-500' }}
                        ">
                            {{ $day['day'] }}
                        </div>

                        {{-- ENTRIES LIST --}}
                        <div class="relative z-10 space-y-1">
                            {{-- HOSTING  --}}
                            @foreach($hosting as $record)
                                <div class="flex w-full mx-1 rounded-md overflow-hidden shadow hover:opacity-90 text-xs">
                                    <div class="w-8 px-2 py-1 flex items-center justify-center bg-primary-700 text-white dark:bg-primary-900">
                                        <b>{{ $record->client->code }}</b>
                                    </div>
                                    <div class="flex flex-col flex-grow px-2 py-1 bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200">
                                        <span class="text-[8px] font-bold leading-none">HOSTING</span>
                                        <span class="truncate pr-2">{{ $record->name }}</span>
                                    </div>
                                </div>
                            @endforeach

                            {{-- DOMAINS  --}}
                            @foreach($domains as $record)
                                <div class="flex w-full mx-1 rounded-md overflow-hidden shadow hover:opacity-90 text-xs">
                                    <div class="w-8 px-2 py-1 flex items-center justify-center bg-primary-700 text-white dark:bg-primary-900">
                                        <b>{{ $record->client->code }}</b>
                                    </div>
                                    <div class="flex flex-col flex-grow px-2 py-1 bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200">
                                        <span class="text-[8px] font-bold leading-none">DOMAIN</span>
                                        <span class="truncate pr-2">{{ $record->name }}</span>
                                    </div>
                                </div>
                            @endforeach

                            @foreach($entries as $entry)
                                @php
                                    if ($entry->entry_type == 'payback')
                                        $entryColour = 'bg-red-200 text-gray-800 dark:bg-red-700 dark:text-white';
                                    elseif ($entry->entry_type == 'prebill')
                                        $entryColour = 'bg-primary-200 text-gray-800 dark:bg-primary-700 dark:text-white';
                                    else
                                        $entryColour = 'bg-gray-200 text-gray-800 dark:text-gray-200 dark:bg-gray-600';

                                @endphp
                                <div class="flex mx-1 rounded-md overflow-hidden shadow hover:cursor-pointer hover:opacity-90 text-xs" wire:click.stop="openEditEntryModal('{{ $entry->id }}')">
                                    <div class="w-8 px-2 py-1 flex items-center justify-center bg-primary-700 text-white dark:bg-primary-900 dark:text-white">
                                        <b>{{ $entry->project->client->code }}</b>
                                    </div>
                                    <div class="flex flex-grow px-2 py-1 items-center justify-between {{ $entryColour  }}">
                                        <span class="flex-grow truncate">{{ $entry->project->name ?? 'No project' }}</span>
                                        <span class="inline-block w-8 text-right">
                                            {{ ($entry->entry_type == 'payback') ? '-' : '' }}{{ round($entry->duration / 60, 2) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach

                            {{-- DAILY TOTAL HOURS --}}
                            @if($hours > 0)
                                <div class="flex mx-1 mt-1 rounded-md px-2 py-1 text-xs text-gray-800 dark:text-gray-100">
                                    <span class="flex-grow text-right pr-2 font-semibold">Total</span>
                                    <span class="inline-block w-8 text-right font-semibold">{{ $hours }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

            </div>
        @endforeach
    </div>
</div>

{{-- Keep Tailwind classes in build --}}
<div class="hidden
    bg-primary-500 bg-primary-600
    bg-gray-100 bg-gray-200 bg-gray-700 bg-gray-800 dark:bg-gray-900
    bg-blue-700 dark:bg-blue-600
"></div>
