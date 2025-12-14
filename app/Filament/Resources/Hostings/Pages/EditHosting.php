<?php

namespace App\Filament\Resources\Hostings\Pages;

use App\Filament\Resources\Hostings\HostingResource;
use App\Models\Hosting;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditHosting extends EditRecord
{
    protected static string $resource = HostingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('disable')
                ->label('Disable Hosting')
                ->visible(fn (Hosting $record) => $record->active)
                ->color('danger')
                ->action(function (Hosting $record) {
                    $record->update(['active' => false]);
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->requiresConfirmation(),
            Action::make('activate')
                ->label('Activate Hosting')
                ->visible(fn (Hosting $record) => !$record->active)
                ->action(function (Hosting $record) {
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
