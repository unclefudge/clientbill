{{-- List View --}}
<div class="h-[calc(100vh-180px)] overflow-y-auto pr-2">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

        <!-- LEFT: 3/4 -->
        <div class="md:col-span-3">
            <div class="rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">

                <table class="min-w-full text-sm text-left border-collapse bg-white text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                    <thead class="bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                    <tr>
                        <th class="w-32 pl-6 py-3 border-b border-gray-200 dark:border-gray-700">Date</th>
                        <th class="w-40 px-3 py-3 border-b border-gray-200 dark:border-gray-700">Client</th>
                        <th class="px-3 py-3 border-b border-gray-200 dark:border-gray-700 w-auto">Activity</th>
                        <th class="w-24 px-3 py-3 border-b border-gray-200 dark:border-gray-700">Hours</th>
                        <th class="w-24 px-3 py-3 border-b border-gray-200 dark:border-gray-700">Type</th>
                        <th class="w-24 px-3 py-3 border-b border-gray-200 dark:border-gray-700">Invoiced</th>
                    </tr>
                    </thead>

                    <tbody class="bg-white dark:bg-gray-900 dark:text-gray-200">

                    @if (count($calSummary))
                        @foreach ($calEntries as $dayPlan)
                            @foreach ($dayPlan['entries'] as $entry)
                                @if ($entry->date->format('m') == $currentMonth->format('m'))
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" wire:click="openEditEntryModal('{{ $entry->id }}')">

                                        {{-- Date --}}
                                        <td class="px-3 pl-6 py-5 border-b border-gray-200 dark:border-gray-700">
                                            <b>{{ $entry->date->format('M j') }} {{ $currentMonth->format('M') }}</b>
                                        </td>

                                        {{-- Client --}}
                                        <td class="px-3 py-3 border-b border-gray-200 dark:border-gray-700">
                                            <div class="flex items-center gap-2">
                                                <span class="truncate"> {{ $entry->project->client->name }}</span>
                                            </div>

                                        </td>

                                        {{-- Activity --}}
                                        <td class="px-3 py-5 border-b border-gray-200 dark:border-gray-700">
                                            {{ $entry->activity }}
                                        </td>

                                        {{-- Duration --}}
                                        <td class="px-3 py-3 border-b border-gray-200 dark:border-gray-700 font-bold">
                                            {{ $entry->duration_hours }} hr
                                        </td>

                                        {{-- Entry Type --}}
                                        <td class="px-3 py-3 border-b border-gray-200 dark:border-gray-700">
                                            {!! $entry->entry_type_badge !!}
                                        </td>

                                        {{-- Entry Type --}}
                                        <td class="px-3 py-3 border-b border-gray-200 dark:border-gray-700 text-center">
                                            @if ($entry->invoice_id)
                                                <div x-data @click="window.location.href = '/invoice/{{$entry->invoice_id}}'"
                                                     class="inline-flex items-center justify-center w-5 h-5 rounded-full hover:cursor-pointer border border-green-500/40 text-green-500 dark:border-green-400/40 dark:text-green-400 bg-green-500/10 dark:bg-green-400/10">
                                                    <x-heroicon-s-check class="w-3.5 h-3.5"/>
                                                </div>
                                            @else
                                                <div class="inline-flex items-center justify-center w-5 h-5 rounded-full border border-red-500/40 text-red-500 dark:border-red-400/40 dark:text-red-400 bg-red-500/10 dark:bg-red-400/10">
                                                    <x-heroicon-s-x-mark class="w-3.5 h-3.5"/>
                                                </div>
                                            @endif
                                        </td>

                                    </tr>
                                @endif
                            @endforeach
                        @endforeach

                    @else
                        <tr>
                            <td colspan="5" class="text-center text-lg py-24 dark:text-gray-400">No Bacon This Month :(</td>
                        </tr>
                    @endif

                    </tbody>

                </table>
            </div>
        </div>

        <!-- RIGHT: 1/4 -->
        <div class="md:col-span-1">
            <!-- ENTRIES -->
            @foreach ($calSummary as $client => $data)
                <div class="rounded-xl mb-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm text-left bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200">
                        <thead class="bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th colspan="2" class="pl-6 py-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex flex-grow items-center justify-between">
                                    <span class="flex-grow truncate"> {{$client}}</span>
                                    <span class="inline-block w-24">
                                        @if ($data['hours_unbilled'] > 0)
                                            <div class="flex items-center gap-1 text-primary-500 hover:text-primary-400 hover:cursor-pointer">
                                                <x-heroicon-s-arrow-left-end-on-rectangle class="w-5 h-5"/><span>Invoice</span>
                                            </div>
                                        @endif
                                    </span>
                                </div>
                            </th>
                        </tr>
                        </thead>
                        <tbody class="dark:text-gray-200">
                        @foreach ($data['projects'] as $project => $hours)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <td class="w-16 px-3 pl-6 py-5 border-b border-gray-200 dark:border-gray-700">
                                    <b>{{ $hours }}</b>
                                </td>
                                <td class="px-3 pl-6 py-5 border-b border-gray-200 dark:border-gray-700">
                                    <b>{{ $project }}</b>
                                </td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                            <td class="w-16 px-3 pl-6 py-5 border-b border-gray-200 dark:border-gray-700">
                                <b>{{ $data['hours_total'] }}</b>
                            </td>
                            <td class="px-3 pl-6 py-5 border-b border-gray-200 dark:border-gray-700">
                                <b>Total</b>
                                @if ($data['hours_unbilled'] > 0)
                                    <span class="ml-5 text-primary-500">({{ $data['hours_unbilled'] }} unbilled)</span>
                                @endif
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            @endforeach

            <!-- HOSTING -->
            @if (!empty($calHosting) && count($calHosting) > 0)
                <div class="rounded-xl mb-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm text-left bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200">
                        <thead class="bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th colspan="2" class="pl-6 py-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex flex-grow items-center justify-between text-primary-500">
                                    <span class="flex-grow truncate">Hosting</span>
                                    <span class="inline-block w-24 text-right pr-6 ">#{{count($calHosting)}}</span>
                                </div>
                            </th>
                        </tr>
                        </thead>
                        <tbody class="dark:text-gray-200">
                        @foreach ($calHosting as $hosting)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <td class="w-16 px-3 pl-6 py-5 border-b border-gray-200 dark:border-gray-700">
                                    <b>{{ $hosting->client->code }}</b>
                                </td>
                                <td class="px-3 pl-6 py-5 border-b border-gray-200 dark:border-gray-700">
                                    <b>{{ $hosting->name }}</b>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- DOMAINS -->
            @if (!empty($calDomains) && count($calDomains) > 0)
                <div class="rounded-xl mb-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm text-left bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200">
                        <thead class="bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th colspan="2" class="pl-6 py-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex flex-grow items-center justify-between">
                                    <span class="flex-grow truncate">Domains</span>
                                    <span class="inline-block w-24 text-right pr-6 ">#{{count($calDomains)}}</span>
                                </div>
                            </th>
                        </tr>
                        </thead>
                        <tbody class="dark:text-gray-200">
                        @foreach ($calDomains as $domain)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <td class="w-16 px-3 pl-6 py-5 border-b border-gray-200 dark:border-gray-700">
                                    <b>{{ $domain->client->code }}</b>
                                </td>
                                <td class="px-3 pl-6 py-5 border-b border-gray-200 dark:border-gray-700">
                                    <b>{{ $domain->name }}</b>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
</div>


