<?php

namespace App\Filament\Resources\Domains\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DomainForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['sm' => 1, 'md' => 12, 'lg' => 12])
            ->components([
                TextInput::make('name')
                    ->columnSpan(['sm' => 1, 'md' => 6, 'lg' => 6]),
                Select::make('client_id')
                    ->columnSpan(['sm' => 1, 'md' => 6, 'lg' => 6])
                    ->relationship('client', 'name')
                    ->required(),
                DatePicker::make('date')
                    ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4]),
                TextInput::make('rate')
                    ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4])
                    ->numeric(),
                TextInput::make('renewal')
                    ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4])
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('description')
                ->columnSpanFull(),
                Textarea::make('summary')
                    ->columnSpanFull(),

            ]);
    }
}
