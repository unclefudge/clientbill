{{-- List View --}}
<div class="h-[calc(100vh-180px)] overflow-y-auto pr-2">
    <div class="rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        {{ $this->table }}
    </div>
</div>
{{--}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

        <!-- LEFT: 3/4 -->
        <div class="md:col-span-4">
            <div class="rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                {{ $this->table }}
            </div>
        </div>

        <!-- RIGHT: 1/4 -->
        {<div class="md:col-span-1">
            @foreach ($invoiceSummary as $client => $data)
                @if ($data['hours_unbilled'] > 0)
                    <div class="rounded-xl mb-5 shadow-sm border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-sm text-left bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200">
                            <thead class="bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                            <tr>
                                <th colspan="3" class="px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                                    <div class="flex flex-grow items-center justify-between">
                                        <span class="flex-grow truncate"> {{$client}}</span>
                                        <span class="inline-block text-primary-500 hover:text-primary-400 hover:cursor-pointer">
                                            {{$data['hours_unbilled']}} hrs
                                        </span>
                                    </div>
                                </th>
                            </tr>
                            </thead>
                            <tbody class="dark:text-gray-400">
                            @foreach ($data['entries_unbilled'] as $entry)
                                <tr class="text-xs hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                    <td class="w-20 px-3 pl-6 py-1 border-b border-gray-200 dark:border-gray-700">
                                        {{ $entry->date->format('M d') }}
                                    </td>
                                    <td class="w-12 px-3 pl-3 py-1 text-center border-b border-gray-200 dark:border-gray-700">
                                        {{ $entry->duration_hours }}
                                    </td>
                                    <td class="px-3 pl-3 py-1 border-b border-gray-200 dark:border-gray-700">
                                        {{ $entry->activity }}
                                    </td>
                                </tr>
                            @endforeach
                            @if ($data['hour_balance_label'] != 'Balanced')
                                <tr class="text-xs text-primary-500 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                    <td colspan="3" class="px-3 pl-6 py-1 border-b border-gray-200 dark:border-gray-700">
                                        {{ $data['hour_balance_label'] }}
                                    </td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                @endif
            @endforeach
        </div>

    </div>
</div>--}}


