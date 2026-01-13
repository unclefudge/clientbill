<?php

namespace App\Filament\Resources\TimeEntries\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TimeEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('project_id')
                    ->relationship('project', 'name')
                    ->required()
                    ->native(false),
                TextInput::make('activity'),
                DatePicker::make('date')
                    ->required(),
                TimePicker::make('start')->native(false),
                TimePicker::make('end')->native(false),
                TextInput::make('duration')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('billable')
                    ->required(),
                Select::make('entry_type')
                    ->label('Entry Type')
                    ->options([
                        'regular' => 'Regular Work',
                        'prebill' => 'Prebill / Claim',
                        'payback' => 'Payback / Delay',
                    ])
                    ->default('regular')
                    ->required()
                    ->helperText('Prebill = charge now. Payback = work now but no charge.'),
                Select::make('invoice_id')
                    ->relationship('invoice', 'id')
                    ->native(false),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
