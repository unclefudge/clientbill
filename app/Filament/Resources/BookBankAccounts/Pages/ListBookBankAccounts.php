<?php
namespace App\Filament\Resources\BookBankAccounts\Pages;
use App\Filament\Resources\BookBankAccounts\BookBankAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListBookBankAccounts extends ListRecords { protected static string $resource = BookBankAccountResource::class; protected function getHeaderActions(): array { return [CreateAction::make()]; } }
