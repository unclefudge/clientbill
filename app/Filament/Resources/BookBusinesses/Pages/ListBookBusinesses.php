<?php
namespace App\Filament\Resources\BookBusinesses\Pages;
use App\Filament\Resources\BookBusinesses\BookBusinessResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListBookBusinesses extends ListRecords { protected static string $resource = BookBusinessResource::class; protected function getHeaderActions(): array { return [CreateAction::make()]; } }
