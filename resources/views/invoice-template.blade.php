<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>
        Invoice {{ isset($activeInvoice->id) && $activeInvoice->id ? str_pad($activeInvoice->id, 5, '0', STR_PAD_LEFT) : 'PREVIEW' }}
    </title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print { display: none !important; }
        }

        body { font-family: sans-serif; }

        .watermark {
            position: fixed;
            top: 30%;
            left: 50%;
            transform: translateX(-50%) rotate(-30deg);
            font-size: 120px;
            color: rgba(180, 180, 180, 0.18);
            pointer-events: none;
            user-select: none;
            z-index: 0;
        }
    </style>
</head>

<body class="bg-white text-black relative">

{{-- PREVIEW WATERMARK --}}
@if (!empty($isPreview) && $isPreview)
    <div class="watermark">DRAFT</div>
@endif

@php
    use Carbon\Carbon;

    // SAFELY PARSE DATES (string or Carbon)
    $issueDate = isset($activeInvoice->issue_date)
        ? Carbon::parse($activeInvoice->issue_date)
        : Carbon::now();

    $dueDate = isset($activeInvoice->due_date)
        ? Carbon::parse($activeInvoice->due_date)
        : Carbon::now()->addDays(7);

    // SAFELY GET CLIENT (in preview it's an array)
    $client = is_array($activeInvoice->client ?? null)
        ? (object) $activeInvoice->client
        : ($activeInvoice->client ?? null);

    $hostingRows = $invoiceItems['hosting'] ?? [];
    $projectRows = $invoiceItems['projects'] ?? [];
    $domainRow   = $invoiceItems['domains'] ?? null;

    $subtotal = $activeInvoice->subtotal ?? 0;
    $gst      = $activeInvoice->gst ?? 0;
    $total    = $activeInvoice->total ?? 0;
@endphp

<div class="min-h-screen flex flex-col relative z-10">

    <!-- LOGO + COMPANY INFO -->
    <div class="flex justify-between items-start mb-10 gap-8">

        <div class="text-sm leading-6">
            <img src="/img/openhands_logo_aqua.jpg" class="h-16 opacity-90 mb-4" alt="Open Hands">
        </div>

        <div class="text-sm text-right leading-6">
            <div class="font-semibold uppercase mb-1">Open Hands</div>
            <div class="text-xs">52 Burgundy Rd Howrah, TAS 7018</div>
            <div class="text-xs">0414 849 091</div>
            <div class="text-[10px]">ABN: 15 420 079 851</div>
        </div>
    </div>

    <!-- CLIENT + INVOICE INFO -->
    <div class="flex justify-between items-start mb-10 gap-8">

        <div class="text-sm leading-6">
            <div class="text-xl font-bold uppercase">{{ $client->name ?? 'Client Name' }}</div>
            <div>{{ $client->contact ?? '' }}</div>
            <div class="leading-[1rem]">
                {{ $client->address ?? '' }}<br>
                {{ $client->suburb ?? '' }} {{ $client->state ?? '' }}
            </div>
        </div>

        <div class="text-sm text-right leading-6">
            <div class="flex flex-col items-end space-y-1">

                <div class="flex justify-end gap-3">
                    <span class="text-xs font-semibold uppercase text-gray-500">Invoice No:</span>
                    <span class="w-24 font-bold">
                        {{ isset($activeInvoice->id) && $activeInvoice->id ? str_pad($activeInvoice->id, 5, '0', STR_PAD_LEFT) : 'PREVIEW' }}
                    </span>
                </div>

                <div class="flex justify-end gap-3">
                    <span class="text-xs font-semibold uppercase text-gray-500">Date:</span>
                    <span class="w-24">{{ $issueDate->format('d.m.Y') }}</span>
                </div>

            </div>
        </div>

    </div>

    <!-- TABLE HEADER -->
    <div class="grid grid-cols-12 text-xs font-semibold uppercase text-gray-600 border-b pb-2 mb-2 tracking-wider">
        <div class="col-span-6">Description</div>
        <div class="col-span-2 text-right">Cost</div>
        <div class="col-span-2 text-right">Qty</div>
        <div class="col-span-2 text-right">Amount</div>
    </div>


    <!-- HOSTING ITEMS -->
    @foreach ($hostingRows as $row)
        <div class="grid grid-cols-12 py-2 border-b text-sm">
            <div class="col-span-6">
                <div class="font-semibold">{{ $row['description'] }}</div>

                @if (!empty($row['summary']))
                    <ul class="ml-4 mt-1 text-xs text-gray-700 list-disc">
                        @foreach (preg_split('/\r\n|\r|\n/', trim($row['summary'])) as $line)
                            @if (strlen(trim($line)))
                                <li>{{ trim($line) }}</li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="col-span-2 text-right">${{ number_format($row['rate'], 0) }}</div>
            <div class="col-span-2 text-right">{{ $row['quantity'] }}</div>
            <div class="col-span-2 text-right">${{ number_format($row['total'], 2) }}</div>
        </div>
    @endforeach


    <!-- DOMAIN ROW -->
    @if ($domainRow)
        <div class="grid grid-cols-12 py-2 border-b text-sm">

            <div class="col-span-6">
                <div class="font-semibold">{{ $domainRow['description'] }}</div>
            </div>

            <div class="col-span-2 text-right">
                @if ($domainRow['rateMin'] == $domainRow['rateMax'])
                    ${{ number_format($domainRow['rateMin'], 0) }}
                @else
                    ${{ number_format($domainRow['rateMin'], 0) }}–${{ number_format($domainRow['rateMax'], 0) }}
                @endif
            </div>

            <div class="col-span-2 text-right">{{ $domainRow['quantity'] }}</div>
            <div class="col-span-2 text-right">${{ number_format($domainRow['total'], 2) }}</div>
        </div>
    @endif


    <!-- PROJECT ITEMS -->
    @foreach ($projectRows as $row)
        <div class="grid grid-cols-12 py-2 border-b text-sm">

            <div class="col-span-6">
                <div class="font-semibold">{{ $row['project_name'] }} Support</div>

                @if (!empty($row['summary_bullets']))
                    <ul class="ml-4 mt-1 text-xs text-gray-700 list-disc">
                        @foreach ($row['summary_bullets'] as $b)
                            <li>{{ $b }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="col-span-2 text-right">${{ number_format($row['rate'], 0) }}/hr</div>
            <div class="col-span-2 text-right">{{ $row['qty'] }}</div>
            <div class="col-span-2 text-right">${{ number_format($row['total'], 2) }}</div>
        </div>
    @endforeach


    <!-- TOTALS -->
    <div class="mt-6 space-y-1 text-sm md:w-1/3 md:ml-auto">
        <div class="flex justify-between">
            <span class="text-xs uppercase text-gray-600">Subtotal</span>
            <span class="font-semibold">${{ number_format($subtotal, 2) }}</span>
        </div>

        <div class="flex justify-between">
            <span class="text-xs uppercase text-gray-600">GST</span>
            <span>${{ number_format($gst, 2) }}</span>
        </div>

        <div class="flex justify-between border-t pt-2">
            <span class="text-xs uppercase font-semibold text-gray-600">Total</span>
            <span class="font-bold">${{ number_format($total, 2) }}</span>
        </div>
    </div>


    <!-- PAYMENT INFO -->
    <div class="grid grid-cols-2 gap-8 mt-auto pt-12">

        <div class="text-sm leading-6">
            <div class="font-semibold mb-2">Payment Information</div>
            <div class="text-xs">Open Hands</div>
            <div class="text-xs">BSB: 067-167</div>
            <div class="text-xs">Account: 18252956</div>
            <div class="text-xs">Pay by: {{ $dueDate->format('d F Y') }}</div>
        </div>

    </div>


    <!-- PRINT BUTTON -->
    <div class="text-center mt-10 no-print">
        <a href="#" onclick="window.print()"
           class="px-6 py-2 bg-black text-white rounded-lg shadow hover:bg-gray-800">
            Print Invoice
        </a>
    </div>

</div>

</body>
</html>
