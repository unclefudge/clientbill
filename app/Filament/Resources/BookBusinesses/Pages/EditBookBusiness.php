<?php

namespace App\Filament\Resources\BookBusinesses\Pages;

use App\Filament\Resources\BookBusinesses\BookBusinessResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBookBusiness extends EditRecord
{
    protected static string $resource = BookBusinessResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }
}
