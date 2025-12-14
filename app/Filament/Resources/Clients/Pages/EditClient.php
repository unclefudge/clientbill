<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use App\Models\Domain;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('disable')
                ->label('Disable Client')
                ->visible(fn (Client $record) => $record->active)
                ->color('danger')
                ->action(function (Client $record) {
                    $record->update(['active' => false]);
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->requiresConfirmation(),
            Action::make('activate')
                ->label('Activate Client')
                ->visible(fn (Client $record) => !$record->active)
                ->action(function (Client $record) {
                    $record->update(['active' => true]);
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->requiresConfirmation()
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
