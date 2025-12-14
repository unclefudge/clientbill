<?php

use Illuminate\Support\Facades\Route;
use App\Models\Invoice;
use App\Http\Controllers\InvoicePdfController;

/*
|--------------------------------------------------------------------------
| Setup Routes (no auth)
|--------------------------------------------------------------------------
*/

//Route::get('/setup/db', '\App\Http\Controllers\SetupController@setupDB');
//Route::get('/setup/import', '\App\Http\Controllers\SetupController@import');
//Route::get('/setup/import2', '\App\Http\Controllers\SetupController@import2');
//Route::get('/setup/import3', '\App\Http\Controllers\SetupController@import3');
//Route::get('/setup/import4', '\App\Http\Controllers\SetupController@import4');
//Route::get('/setup/import0', '\App\Http\Controllers\SetupController@importAll');
//Route::get('/setup/checkimport', '\App\Http\Controllers\SetupController@checkImport');
Route::get('/quick', '\App\Http\Controllers\SetupController@quick');
/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
|
| All preview/print/PDF invoice actions require the user to be logged in.
| These are intentionally placed OUTSIDE Filament routing so they can be
| accessed by Browsershot and external links.
|
*/

Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Invoice Preview (no invoice record, uses generated preview data)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth'])->get('/invoice-preview', function () {

        $encoded = request('previewData');

        // No preview data → return a simple "Invalid preview" message or abort
        if (!$encoded) {
            return "<h2 style='padding: 2rem; font-family: sans-serif;'>No invoice preview data supplied.</h2>";
        }

        $payload = json_decode(base64_decode($encoded), true);

        if (!$payload || !isset($payload['invoice'], $payload['items'])) {
            return "<h2 style='padding: 2rem; font-family: sans-serif;'>Invalid preview payload.</h2>";
        }

        return view('invoice-template', [
            'activeInvoice' => (object) $payload['invoice'],
            'invoiceItems'  => $payload['items'],
            'isPreview'     => true,
        ]);
    })->name('invoice.preview');


    /*
    |--------------------------------------------------------------------------
    | Printable Invoice (real invoice)
    |--------------------------------------------------------------------------
    */
    Route::get('/invoice/{invoice}/print', function (Invoice $invoice) {
        return view('invoice-template', [
            'activeInvoice' => $invoice,
            'invoiceItems'  => $invoice->getInvoiceItems(),
            'isPreview'     => false,
        ]);
    })->name('invoice.print.view');


    /*
    |--------------------------------------------------------------------------
    | PDF Download (Browsershot loads signed route)
    |--------------------------------------------------------------------------
    */
    Route::get('/invoice/{invoice}/pdf', [InvoicePdfController::class, 'show'])
        ->name('invoice.pdf');
});


/*
|--------------------------------------------------------------------------
| Signed Route for Browsershot Rendering Only
|--------------------------------------------------------------------------
|
| Browsershot loads this route WITHOUT authentication. Signature ensures
| it cannot be accessed manually.
|
*/

Route::get('/invoice/{invoice}/print-signed', function (Invoice $invoice) {
    return view('invoice-template', [
        'activeInvoice' => $invoice,
        'invoiceItems'  => $invoice->getInvoiceItems(),
        'isPreview'     => false,
    ]);
})->name('invoice.print.signed')->middleware('signed');
