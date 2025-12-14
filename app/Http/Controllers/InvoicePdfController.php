<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\URL;

class InvoicePdfController extends Controller
{
    public function show(Invoice $invoice)
    {
        //$url = url('/invoice/' . $invoice->id . '/print');
        $url = URL::signedRoute('invoice.print.signed', ['invoice' => $invoice->id], absolute: true);

        // Ensure directory exists
        $tmpPath = storage_path('app/tmp');
        if (!is_dir($tmpPath)) {
            mkdir($tmpPath, 0755, true);
        }

        $pdfPath = $tmpPath . "/invoice-{$invoice->id}.pdf";

        Browsershot::url($url)
            ->timeout(60)
            ->waitUntilNetworkIdle()
            ->showBackground()
            ->format('A4')
            ->margins(0, 0, 0, 0)
            ->save($pdfPath);

        //return response()->file($pdfPath);
        // return response()->download($pdfPath, "Invoice-{$invoice->id}.pdf");
        return response()->download($pdfPath, "{$invoice->client->name} Invoice-{$invoice->id}.pdf", [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
