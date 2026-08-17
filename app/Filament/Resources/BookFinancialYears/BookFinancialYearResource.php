<?php

namespace App\Filament\Resources\BookFinancialYears;

use App\Filament\Resources\BookFinancialYears\Pages\CreateBookFinancialYear;
use App\Filament\Resources\BookFinancialYears\Pages\EditBookFinancialYear;
use App\Filament\Resources\BookFinancialYears\Pages\ListBookFinancialYears;
use App\Models\BookFinancialYear;
use App\Support\Books\BookContext;
use BackedEnum;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookFinancialYearResource extends Resource
{
    protected static ?string $model = BookFinancialYear::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;
    protected static ?string $navigationLabel = 'Financial Years';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'books/settings/financial-years';

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessBooks() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $business = app(BookContext::class)->current();

        return parent::getEloquentQuery()
            ->where('book_business_id', $business?->id ?? 0);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('book_business_id')
                ->default(fn () => app(BookContext::class)->current()?->id)
                ->required(),

            TextInput::make('start_year')
                ->label('Start year')
                ->numeric()
                ->minValue(1900)
                ->maxValue(2200)
                ->required()
                ->helperText('Books will use 1 July of this year to 30 June of the following year.'),

            Select::make('status')
                ->native(false)
                ->options([
                    'open' => 'Open',
                    'closed' => 'Closed',
                ])
                ->default('open')
                ->required()
                ->helperText('Closed years are locked against accidental transaction changes.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('label')->sortable(),
            TextColumn::make('status')
                ->badge()
                ->formatStateUsing(fn (string $state): string => ucfirst($state))
                ->color(fn (string $state): string => $state === 'open' ? 'success' : 'danger'),
            TextColumn::make('start_date')->date('j M Y'),
            TextColumn::make('end_date')->date('j M Y'),
        ])->defaultSort('start_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookFinancialYears::route('/'),
            'create' => CreateBookFinancialYear::route('/create'),
            'edit' => EditBookFinancialYear::route('/{record}/edit'),
        ];
    }
}
