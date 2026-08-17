<?php

namespace App\Filament\Pages\Books;

use App\Models\BookBusiness;
use App\Support\Books\BookContext;
use Filament\Pages\Page;

abstract class BookPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    public function mountBookContext(): void
    {
        abort_unless(app(BookContext::class)->current(), 403);
    }

    public function getBookBusiness(): BookBusiness
    {
        return app(BookContext::class)->current() ?? abort(403);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessBooks() ?? false;
    }

    public function getHeading(): string
    {
        return '';
    }
}
