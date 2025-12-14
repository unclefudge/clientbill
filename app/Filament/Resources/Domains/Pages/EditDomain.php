<?php

namespace App\Filament\Resources\Domains\Pages;

use App\Filament\Resources\Domains\DomainResource;
use App\Models\Domain;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDomain extends EditRecord
{
    protected static string $resource = DomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('disable')
                ->label('Disable Domain')
                ->visible(fn (Domain $record) => $record->active)
                ->color('danger')
                ->action(function (Domain $record) {
                    $record->update(['active' => false]);
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->requiresConfirmation(),
            Action::make('activate')
                ->label('Activate Domain')
                ->visible(fn (Domain $record) => !$record->active)
                ->action(function (Domain $record) {
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
