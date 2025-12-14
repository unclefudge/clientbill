<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ListInvoices extends Page
{
    protected string $view = 'filament.pages.invoice-list';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Invoices';
    protected static ?string $slug = 'invoice';
    protected static bool $shouldRegisterNavigation = true;

    public function getHeading(): string
    {
        return '';
    }
}
