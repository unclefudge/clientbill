<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Domain;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('disable')
                ->label('Disable Project')
                ->visible(fn (Project $record) => $record->active)
                ->color('danger')
                ->action(function (Project $record) {
                    $record->update(['active' => false]);
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->requiresConfirmation(),
            Action::make('activate')
                ->label('Activate Project')
                ->visible(fn (Project $record) => !$record->active)
                ->action(function (Project $record) {
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
