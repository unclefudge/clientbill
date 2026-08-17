<?php

namespace App\Filament\Resources\BookFinancialYears\Pages;

use App\Filament\Resources\BookFinancialYears\BookFinancialYearResource;
use App\Models\BookFinancialYear;
use App\Models\BookTransaction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBookFinancialYear extends EditRecord
{
    protected static string $resource = BookFinancialYearResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['start_year'] = $this->record->start_date?->year;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $range = BookFinancialYear::rangeForStartYear((int) $data['start_year']);

        unset($data['start_year']);

        return [...$data, ...$range];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn (): bool => $this->hasTransactions())
                ->tooltip(fn (): ?string => $this->hasTransactions()
                    ? 'This financial year has transaction history. Keep it and use Open / Closed instead.'
                    : null),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }

    private function hasTransactions(): bool
    {
        return BookTransaction::query()
            ->where('book_business_id', $this->record->book_business_id)
            ->whereBetween('transaction_date', [
                $this->record->start_date->toDateString(),
                $this->record->end_date->toDateString(),
            ])
            ->exists();
    }
}
