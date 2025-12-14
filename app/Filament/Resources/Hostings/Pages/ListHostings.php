<?php

namespace App\Filament\Resources\Hostings\Pages;

use App\Filament\Resources\Hostings\HostingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListHostings extends ListRecords
{
    protected static string $resource = HostingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Active')
                ->icon('heroicon-s-eye')
                ->modifyQueryUsing(function ($query) {
                    return $query->where('active', true);
                }),
            'disabled' => Tab::make('Disabled')
                ->icon('heroicon-o-eye-slash')
                ->modifyQueryUsing(function ($query) {
                    return $query->where('active', false);
                }),
        ];
    }
}
