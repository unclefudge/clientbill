<?php

namespace App\Filament\Resources\BookBankAccounts\Pages;

use App\Filament\Resources\BookBankAccounts\BookBankAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBookBankAccount extends EditRecord
{
    protected static string $resource = BookBankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }
}
