<?php

namespace App\Filament\Resources\BookCategories\Pages;

use App\Filament\Resources\BookCategories\BookCategoryResource;
use App\Models\BookImportRow;
use App\Models\BookTransaction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBookCategory extends EditRecord
{
    protected static string $resource = BookCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn (): bool => $this->categoryIsInUse())
                ->tooltip(fn (): ?string => $this->categoryIsInUse()
                    ? 'This category has transaction history. Set it inactive instead so historical reports keep their category.'
                    : null),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }

    private function categoryIsInUse(): bool
    {
        $categoryId = $this->record->getKey();

        if (BookTransaction::query()->where('book_category_id', $categoryId)->exists()) {
            return true;
        }

        return BookImportRow::query()
            ->where(function ($query) use ($categoryId): void {
                $query->where('book_category_id', $categoryId)->orWhere('suggested_book_category_id', $categoryId);
            })
            ->exists();
    }
}
