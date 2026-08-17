<?php

namespace App\Filament\Resources\BookCategoryRules\Pages;

use App\Filament\Resources\BookCategoryRules\BookCategoryRuleResource;
use App\Models\BookCategory;
use App\Support\Books\BookContext;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBookCategoryRule extends EditRecord
{
    protected static string $resource = BookCategoryRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $businessId = app(BookContext::class)->current()?->id ?? 0;

        BookCategory::query()
            ->where('book_business_id', $businessId)
            ->findOrFail($data['book_category_id']);

        $data['book_business_id'] = $this->record->book_business_id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }
}
