<?php

namespace App\Filament\Resources\Hostings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HostingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client.name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('domain')
                    ->searchable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('rate')
                    ->label('Cost')
                    ->numeric()
                    ->formatStateUsing(fn($state) => '$' . number_format($state, 0)),
                IconColumn::make('ssl')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            //->filters([//])
            //->recordActions([EditAction::make(),])
            //->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make(),]),])
            ->defaultSort('order')
            ->reorderable('order');
    }
}
