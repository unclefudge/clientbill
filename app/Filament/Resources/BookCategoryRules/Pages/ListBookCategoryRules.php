<?php

namespace App\Filament\Resources\BookCategoryRules\Pages;

use App\Filament\Resources\BookCategoryRules\BookCategoryRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBookCategoryRules extends ListRecords
{
    protected static string $resource = BookCategoryRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
