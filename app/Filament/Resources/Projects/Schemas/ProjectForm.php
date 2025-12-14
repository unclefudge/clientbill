<?php

namespace App\Filament\Resources\Projects\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['sm' => 1, 'md' => 12, 'lg' => 12])
            ->components([
                Select::make('client_id')
                    ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4])
                    ->relationship('client', 'name')
                    ->required()
                    ->native(false),
                TextInput::make('name')
                    ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4])
                    ->required(),
                TextInput::make('rate')
                    ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4])
                    ->label('Cost')
                    ->numeric()
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }
}
