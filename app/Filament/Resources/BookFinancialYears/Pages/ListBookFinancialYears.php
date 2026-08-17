<?php
namespace App\Filament\Resources\BookFinancialYears\Pages;
use App\Filament\Resources\BookFinancialYears\BookFinancialYearResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListBookFinancialYears extends ListRecords { protected static string $resource = BookFinancialYearResource::class; protected function getHeaderActions(): array { return [CreateAction::make()]; } }
