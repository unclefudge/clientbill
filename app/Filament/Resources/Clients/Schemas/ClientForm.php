<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['sm' => 1, 'md' => 12, 'lg' => 12])
            ->components([
                TextInput::make('name')
                    ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4])
                    ->required(),
                TextInput::make('contact')
                    ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4]),
                TextInput::make('rate')
                    ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4])
                    ->required()
                    ->numeric()
                    ->default(85.0),
                TextInput::make('email')
                    ->columnSpan(['sm' => 1, 'md' => 6, 'lg' => 6])
                    ->label('Email address')
                    ->email(),
                TextInput::make('phone')
                    ->columnSpan(['sm' => 1, 'md' => 6, 'lg' => 6])
                    ->tel(),
                TextInput::make('address')
                    ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4]),
                TextInput::make('suburb')
                    ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4]),
                TextInput::make('state')
                    ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                TextInput::make('postcode')
                    ->columnSpan(['sm' => 1, 'md' => 2, 'lg' => 2]),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
