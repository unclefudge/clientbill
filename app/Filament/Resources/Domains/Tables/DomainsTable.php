<?php

namespace App\Filament\Resources\Domains\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DomainsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('client.name')
                    ->searchable(),
                TextColumn::make('date')
                    ->label('Commencement Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('rate')
                    ->label('Cost')
                    ->numeric()
                    ->formatStateUsing(fn ($state) => '$' . number_format($state, 0))
                    ->sortable(),
                TextColumn::make('renewal')
                    ->label('Renewal Period')
                    ->numeric()
                    ->formatStateUsing(fn ($state) => "$state yrs")
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            //->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make(),]),])
            ->defaultSort('name')
            ->reorderable('order');
    }
}
