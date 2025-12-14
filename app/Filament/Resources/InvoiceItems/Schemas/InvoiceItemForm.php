<?php

namespace App\Filament\Resources\InvoiceItems\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InvoiceItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('invoice_id')
                    ->relationship('invoice', 'id')
                    ->required(),
                Select::make('time_entry_id')
                    ->relationship('timeEntry', 'id'),
                TextInput::make('description')
                    ->required(),
                Textarea::make('summary')
                    ->label('Summary (optional)')
                    ->rows(4)
                    ->placeholder("• Bullet point 1\n• Bullet point 2\n• Bullet point 3")
                    ->columnSpan('full'),
                TextInput::make('hours')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('rate')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
            ]);
    }
}
