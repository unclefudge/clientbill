<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ViewInvoice extends Page
{

    protected string $view = 'filament.pages.invoice-view';
    protected static ?string $slug = 'invoice/{invoice}';
    protected static bool $shouldRegisterNavigation = false;

    public Invoice $invoice;

    public function mount(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function getTitle(): string|Htmlable
    {
        return "Invoice #{$this->invoice->id}";
    }

    public function getHeading(): string
    {
        return '';
    }
}
