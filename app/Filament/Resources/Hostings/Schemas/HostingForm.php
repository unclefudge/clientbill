<?php

namespace App\Filament\Resources\Hostings\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class HostingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['sm' => 1, 'md' => 12, 'lg' => 12])
            ->components([
                Select::make('client_id')
                    ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4])
                    ->relationship('client', 'name')
                    ->required(),
                TextInput::make('name')
                    ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4]),
                TextInput::make('domain')
                    ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4]),
                DatePicker::make('date')
                    ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4]),
                TextInput::make('rate')
                    ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4])
                    ->numeric(),
                Toggle::make('ssl')
                    ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4])
                    ->label('SSL certicate included')
                    ->inline(false)
                    ->required(),
                TextInput::make('description')
                    ->columnSpanFull(),
                Textarea::make('summary')
                    ->columnSpanFull(),

            ]);
    }
}
