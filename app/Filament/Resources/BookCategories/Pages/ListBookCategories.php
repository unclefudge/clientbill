<?php

namespace App\Filament\Resources\BookCategories\Pages;

use App\Filament\Resources\BookCategories\BookCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListBookCategories extends ListRecords
{
    protected static string $resource = BookCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'income' => Tab::make('Income')->modifyQueryUsing(fn ($query) => $query->where('type', 'income')),
            'expense' => Tab::make('Expense')->modifyQueryUsing(fn ($query) => $query->where('type', 'expense')),
            //'other' => Tab::make('Other')->modifyQueryUsing(fn ($query) => $query->where('type', 'other')),
        ];
    }
}
