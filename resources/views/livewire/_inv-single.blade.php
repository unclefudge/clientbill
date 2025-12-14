{{-- Single View --}}
<div class="h-[calc(100vh-180px)] overflow-y-auto pr-2">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

        <!-- LEFT: 3/4 -->
        <div class="md:col-span-3">
            <div class="rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">

                <div class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-6">

                    <!-- =========================== -->
                    <!--      HEADER (Two Columns)   -->
                    <!-- =========================== -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">

                        <!-- LEFT: ISSUED TO -->
                        <div>
                            {{-- <div class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 mb-1">
                                Issued To:
                            </div> --}}

                            <div class="text-sm leading-5 text-gray-800 dark:text-gray-200">
                                <b class="text-lg uppercase">{{ $activeInvoice->client->name }}</b><br>
                                {{ $activeInvoice->client->contact }}<br>
                                {{ $activeInvoice->client->address }},<br>
                                {{ $activeInvoice->client->suburb }} {{ $activeInvoice->client->state }}
                            </div>
                        </div>

                        <!-- RIGHT: INVOICE DETAILS -->
                        <div class="text-sm leading-6 text-gray-800 dark:text-gray-200 md:text-right">

                            <div class="flex flex-col items-center md:items-end space-y-0.5">
                                <!-- ViewInvoice No -->
                                <div class="flex items-center md:justify-end gap-3">
                                    <span class="mr-5">{!! $activeInvoice->invoice_badge !!}</span>
                                    <span class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400">Invoice No:</span>
                                    <span class="w-20 font-bold">
                                        {{ str_pad($activeInvoice->id, 5, '0', STR_PAD_LEFT) }}
                                    </span>
                                </div>

                                <!-- Date -->
                                <div class="flex items-center md:justify-end gap-3">
                                    <span class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400">Date:</span>
                                    <span class="w-20">{{ $activeInvoice->issue_date->format('d.m.Y') }}</span>
                                </div>

                                <!-- Due Date -->
                                <div class="flex items-center md:justify-end gap-3">
                                    <span class="text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400">Due Date:</span>
                                    <span class="w-20">{{ $activeInvoice->due_date->format('d.m.Y') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- =========================== -->
                    <!--      TABLE HEADER           -->
                    <!-- =========================== -->
                    <div class="grid grid-cols-12 text-xs font-semibold tracking-wider text-gray-500 dark:text-gray-400 uppercase border-b border-gray-300 dark:border-gray-700 pb-2 mb-2">
                        <div class="col-span-6">Description</div>
                        <div class="col-span-2 text-right">Cost</div>
                        <div class="col-span-2 text-right">Qty</div>
                        <div class="col-span-2 text-right">Amount</div>
                    </div>

                    <!-- =========================== -->
                    <!--      ITEM ROWS              -->
                    <!-- =========================== -->
                    @php
                        if ($this->invoiceItems) {
                        $hostingRows = $this->invoiceItems['hosting'];
                        $projectRows = $this->invoiceItems['projects'];
                        $domainRow = $this->invoiceItems['domains'];
                        $customRows = $this->invoiceItems['custom'];
                        } else {
                            $hostingRows = [];
                            $projectRows = [];
                            $domainRow = [];
                            $customRows = [];
                        }
                    @endphp

                        <!-- HOSTING -->
                    @foreach($hostingRows as $row)
                        <div class="grid grid-cols-12 py-2 border-b border-gray-200 dark:border-gray-800 text-sm">
                            <!-- DESCRIPTION + BULLETS -->
                            <div class="col-span-6">
                                <div class="font-semibold text-gray-900 dark:text-gray-200">{{ $row['description'] }}</div>

                                @if (!empty($row['summary']))
                                    <ul class="ml-4 mt-1 text-xs text-gray-700 dark:text-gray-400 list-disc">
                                        @foreach(collect(preg_split('/\r\n|\r|\n/', trim((string)$row['summary'])))->filter()->values()->toArray() as $b)
                                            <li>{{ $b }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>

                            <!-- RATE / HOURS -->
                            <div class="col-span-2 text-right">
                                ${{ number_format($row['rate'], 0) }}
                            </div>
                            <!-- QTY -->
                            <div class="col-span-2 text-right">
                                {{ number_format($row['quantity'], 0) }}
                            </div>
                            <!-- TOYAL -->
                            <div class="col-span-2 text-right">
                                ${{ number_format($row['amount'], 2) }}
                            </div>
                        </div>
                    @endforeach

                    <!-- DOMAIN (single row) -->
                    @if ($domainRow)
                        <div class="grid grid-cols-12 py-2 border-b border-gray-200 dark:border-gray-800 text-sm">
                            <!-- DESCRIPTION + BULLETS -->
                            <div class="col-span-6">
                                <div class="font-semibold text-gray-900 dark:text-gray-200">{{ $domainRow['description'] }}</div>

                                @if (!empty($domainRow['summary']))
                                    <ul class="ml-4 mt-1 text-xs text-gray-700 dark:text-gray-400 list-disc">
                                        @foreach(collect(preg_split('/\r\n|\r|\n/', trim((string)$domainRow['summary'])))->filter()->values()->toArray() as $b)
                                            <li>{{ $b }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>

                            <!-- RATE / HOURS -->
                            <div class="col-span-2 text-right">
                                @if ($domainRow['rateMin'] == $domainRow['rateMax'])
                                    ${{ number_format($domainRow['rateMin'], 0) }}
                                @else
                                    ${{ number_format($domainRow['rateMin'], 0) }}-${{ number_format($domainRow['rateMax'], 0) }} ea.
                                @endif
                            </div>
                            <!-- QTY -->
                            <div class="col-span-2 text-right">
                                {{ number_format($domainRow['quantity'], 0) }}
                            </div>
                            <!-- TOYAL -->
                            <div class="col-span-2 text-right">
                                ${{ number_format($domainRow['total'], 2) }}
                            </div>
                        </div>
                    @endif


                    <!-- Projects -->
                    @foreach($projectRows as $row)
                        <div class="grid grid-cols-12 py-2 border-b border-gray-200 dark:border-gray-800 text-sm">
                            <!-- DESCRIPTION + BULLETS -->
                            <div class="col-span-6">
                                <div class="font-semibold text-gray-900 dark:text-gray-200">{{ $row['project_name'] }} support</div>

                                @if (!empty($row['summary_bullets']))
                                    <ul class="ml-4 mt-1 text-xs text-gray-700 dark:text-gray-400 list-disc">
                                        @foreach($row['summary_bullets'] as $b)
                                            <li>{{ $b }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>

                            <!-- RATE / HOURS -->
                            <div class="col-span-2 text-right">
                                ${{ abs(number_format($row['rate'], 0)) }}/hr
                            </div>
                            <!-- QTY -->
                            <div class="col-span-2 text-right">
                                {{ $row['qty'] }}
                            </div>
                            <!-- TOYAL -->
                            <div class="col-span-2 text-right">
                                ${{ number_format($row['total'], 2) }}
                            </div>
                        </div>
                    @endforeach

                    <!-- CUSTOM -->
                    @foreach($customRows as $row)
                        <div class="grid grid-cols-12 py-2 border-b border-gray-200 dark:border-gray-800 text-sm">
                            <!-- DESCRIPTION + BULLETS -->
                            <div class="col-span-6">
                                <div class="font-semibold text-gray-900 dark:text-gray-200">{{ $row['description'] }}</div>

                                @if (!empty($row['summary']))
                                    <ul class="ml-4 mt-1 text-xs text-gray-700 dark:text-gray-400 list-disc">
                                        @foreach(collect(preg_split('/\r\n|\r|\n/', trim((string)$row['summary'])))->filter()->values()->toArray() as $b)
                                            <li>{{ $b }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>

                            <!-- RATE / HOURS -->
                            <div class="col-span-2 text-right">
                                ${{ number_format($row['rate'], 0) }}
                            </div>
                            <!-- QTY -->
                            <div class="col-span-2 text-right">
                                {{ number_format($row['quantity'], 0) }}
                            </div>
                            <!-- TOYAL -->
                            <div class="col-span-2 text-right">
                                ${{ number_format($row['amount'], 2) }}
                            </div>
                        </div>
                    @endforeach

                    <!-- =========================== -->
                    <!--      SUMMARY TOTALS         -->
                    <!-- =========================== -->
                    <div class="mt-6 space-y-1 text-sm md:w-1/3 md:ml-auto">

                        <!-- Subtotal -->
                        <div class="flex justify-between">
                            <span class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-semibold">Subtotal</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-200">
                                ${{ number_format($activeInvoice->subtotal, 2) }}
                            </span>
                        </div>

                        <!-- Tax -->
                        <div class="flex justify-between">
                            <span class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">GST</span>
                            <span class="text-gray-800 dark:text-gray-200">
                                ${{ number_format($activeInvoice->gst, 2) }}
                            </span>
                        </div>

                        <!-- TOTAL -->
                        <div class="flex justify-between pt-2 border-t border-gray-300 dark:border-gray-700">
                            <span class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400 font-semibold">Total</span>
                            <span class="font-bold text-gray-900 dark:text-white">
                                ${{ number_format($activeInvoice->total, 2) }}
                            </span>
                        </div>

                    </div>

                </div>
            </div>
        </div>

        <!-- RIGHT: 1/4 -->
        <div class="md:col-span-1">
            <!-- HOSTING -->
            @if (!empty($hostingRows))
                <div class="rounded-xl mb-5 shadow-sm border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm text-left bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200">
                        <thead class="bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th colspan="3" class="px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex flex-grow items-center justify-between">
                                    <span class="flex-grow truncate">Hosting</span>
                                    <span class="flex justify-end w-24">#{{ count($hostingRows) }}</span>
                                </div>
                            </th>
                        </tr>
                        </thead>
                        <tbody class="dark:text-gray-400">
                        @foreach ($hostingRows as $item)
                            <tr class="text-xs hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <td class="w-20 px-3 pl-6 py-1 border-b border-gray-200 dark:border-gray-700">
                                    {{ $item->hosting->date->format('M d') }}
                                </td>
                                <td class="px-3 pl-3 py-1 border-b border-gray-200 dark:border-gray-700">
                                    {{ $item->hosting->domain }}
                                </td>
                                <td class="w-10 px-3 pl-3 py-1 border-b border-gray-200 dark:border-gray-700">
                                    <x-heroicon-s-trash class="w-3 h-3 text-gray-300 dark:text-gray-500 inline hoverDanger hover:cursor-pointer" wire:click.stop="confirmDeleteItem('{{ $item->id }}', 'Task')"/>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- DOMAINS -->
            @if ($domainRow)
                <div class="rounded-xl mb-5 shadow-sm border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm text-left bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200">
                        <thead class="bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th colspan="3" class="px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex flex-grow items-center justify-between">
                                    <span class="flex-grow truncate">Domain names</span>
                                    <span class="flex justify-end w-24">#{{ $domainRow['quantity'] }}</span>
                                </div>
                            </th>
                        </tr>
                        </thead>
                        <tbody class="dark:text-gray-400">
                        @if (!empty($domainRow['summary']))
                            @foreach ($domainRow['items'] as $item)
                                <tr class="text-xs hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                    <td class="w-20 px-3 pl-6 py-1 border-b border-gray-200 dark:border-gray-700">
                                        {{ $item->domain->date->format('M d') }}
                                    </td>
                                    <td class="px-3 pl-3 py-1 border-b border-gray-200 dark:border-gray-700">
                                        {{ $item->domain->name }}
                                    </td>
                                    <td class="w-10 px-3 pl-3 py-1 border-b border-gray-200 dark:border-gray-700">
                                        <x-heroicon-s-trash class="w-3 h-3 text-gray-300 dark:text-gray-500 inline hoverDanger hover:cursor-pointer" wire:click.stop="confirmDeleteItem('{{ $item->id }}', 'Task')"/>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- PROJECTS -->
            @foreach ($projectRows as $row)
                <div class="rounded-xl mb-5 shadow-sm border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm text-left bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200">
                        <thead class="bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th colspan="4" class="px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex flex-grow items-center justify-between">
                                    <span class="flex-grow truncate">{{ $row['project_name'] }} <span wire:click.stop="openEditProjectSummaryModal({{ $row['summary_id'] }})" class="ml-5 text-xs text-primary-500 hover:text-primary-400 hover:cursor-pointer">Edit </span></span>
                                    <span class="flex justify-end w-24"> {{ $row['qty'] }} hrs</span>
                                </div>
                            </th>
                        </tr>
                        </thead>
                        <tbody class="dark:text-gray-400">
                        @foreach ($row['items'] as $item)
                            @if ($item->type == 'time')
                                <tr class="text-xs hover:bg-gray-50 dark:hover:bg-gray-800 hover:cursor-pointer transition-colors">
                                    <td class="w-20 px-3 pl-6 py-1 border-b border-gray-200 dark:border-gray-700">
                                        {{ $item->timeEntry->date->format('M d') }}
                                    </td>
                                    <td class="w-10 px-2 py-1 text-center border-b border-gray-200 dark:border-gray-700">
                                        @if ($item->rate < 0)
                                            <span class="text-red-500">-{{ $item->timeEntry->duration_hours }}</span>
                                        @else
                                            {{ $item->timeEntry->duration_hours }}
                                        @endif
                                    </td>
                                    <td class="px-2 py-1 border-b border-gray-200 dark:border-gray-700">
                                        {{ $item->timeEntry->activity }}
                                    </td>
                                    <td class="w-10 px-2 py-1 border-b border-gray-200 dark:border-gray-700">
                                        <x-heroicon-s-trash class="w-3 h-3 text-gray-300 dark:text-gray-500 inline hoverDanger hover:cursor-pointer" wire:click.stop="confirmDeleteItem('{{ $item->id }}', 'Task')"/>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach

            <!-- CUSTOM -->
            @if (!empty($customRows))
                <div class="rounded-xl mb-5 shadow-sm border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm text-left bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200">
                        <thead class="bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th colspan="3" class="px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex flex-grow items-center justify-between">
                                    <span class="flex-grow truncate">Custom</span>
                                    <span class="flex justify-end w-24">#{{ count($customRows) }}</span>
                                </div>
                            </th>
                        </tr>
                        </thead>
                        <tbody class="dark:text-gray-400">
                        @foreach ($customRows as $item)
                            <tr class="text-xs hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <td class="w-20 px-3 pl-6 py-1 border-b border-gray-200 dark:border-gray-700">
                                    {{ $item->created_at->format('M d') }}
                                </td>
                                <td class="px-3 pl-3 py-1 border-b border-gray-200 dark:border-gray-700">
                                    {{ $item->description }}
                                </td>
                                <td class="w-10 px-3 pl-3 py-1 border-b border-gray-200 dark:border-gray-700">
                                    <x-heroicon-s-trash class="w-3 h-3 text-gray-300 dark:text-gray-500 inline hoverDanger hover:cursor-pointer" wire:click.stop="confirmDeleteItem('{{ $item->id }}', 'Task')"/>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <x-filament::button wire:click.stop="openCreateItemModal()" size="sm" color="gray">Add Invoice Item</x-filament::button>
        </div>

    </div>
</div>


