<?php

namespace App\Filament\Resources\TimeEntries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TimeEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name')->searchable(),
                TextColumn::make('activity')->searchable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                /*TextColumn::make('start')
                    ->time()
                    ->sortable(),
                TextColumn::make('end')
                    ->time()
                    ->sortable(),*/
                TextColumn::make('duration')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if ($state === null) {
                            return '-';
                        }

                        $hours = intdiv($state, 60);
                        $minutes = $state % 60;

                        if ($hours > 0 && $minutes > 0) {
                            return "{$hours}h {$minutes}m";
                        }

                        if ($hours > 0) {
                            return "{$hours}h";
                        }

                        return "{$minutes}m";
                    }),
                IconColumn::make('billable')
                    ->boolean(),
                BadgeColumn::make('entry_type')
                    ->formatStateUsing(fn ($state) => ucwords($state))
                    ->colors([
                        'primary' => 'draft',
                        'warning' => 'payback',
                        'success' => 'regular',
                        'danger'  => 'prebill',
                    ]),
                //TextColumn::make('invoice.id')->searchable(),
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
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
