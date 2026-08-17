<?php

namespace App\Filament\Resources\BookFinancialYears\Pages;

use App\Filament\Resources\BookFinancialYears\BookFinancialYearResource;
use App\Models\BookFinancialYear;
use Filament\Resources\Pages\CreateRecord;

class CreateBookFinancialYear extends CreateRecord
{
    protected static string $resource = BookFinancialYearResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $range = BookFinancialYear::rangeForStartYear((int) $data['start_year']);

        unset($data['start_year']);

        return [...$data, ...$range];
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }
}
