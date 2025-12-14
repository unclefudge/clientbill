<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class Calendar extends Page
{
    protected string $view = 'filament.pages.calendar';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = '/calendar';

    public function getTitle(): string | Htmlable
    {
        return __('Calendar');
    }

    public function getHeading(): string
    {
        return __('');
    }

}
