<?php

namespace App\Filament\Resources\BookCategories;

use App\Filament\Resources\BookCategories\Pages\CreateBookCategory;
use App\Filament\Resources\BookCategories\Pages\EditBookCategory;
use App\Filament\Resources\BookCategories\Pages\ListBookCategories;
use App\Models\BookCategory;
use App\Support\Books\BookContext;
use BackedEnum;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookCategoryResource extends Resource
{
    protected static ?string $model = BookCategory::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;
    protected static ?string $navigationLabel = 'Categories';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'books/settings/categories';

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

            Select::make('type')
                ->native(false)
                ->options([
                    'income' => 'Income',
                    'expense' => 'Expense',
                    'other' => 'Other',
                ])
                ->required(),

            TextInput::make('name')
                ->required()
                ->maxLength(255),

            Select::make('report_treatment')
                ->label('Report treatment')
                ->native(false)
                ->options(BookCategory::REPORT_TREATMENTS)
                ->placeholder('Automatic from type / name')
                ->helperText('Controls Profit & Loss reporting. Leave blank on a new category and Books will choose a sensible default.')
                ->nullable(),

            Select::make('default_gst_treatment')
                ->label('Default GST treatment')
                ->native(false)
                ->options([
                    'gst_applicable' => 'GST applicable',
                    'gst_free' => 'GST free',
                    'excluded' => 'Excluded from BAS',
                ])
                ->required(),

            TextInput::make('default_business_use_percentage')
                ->label('Default business use %')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->suffix('%')
                ->required(),

            Select::make('default_purchase_type')
                ->label('Default purchase type')
                ->native(false)
                ->options([
                    'non_capital' => 'Non-capital',
                    'capital' => 'Capital',
                ])
                ->required(),

            Toggle::make('active')
                ->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'income' => 'success',
                        'expense' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('report_treatment')
                    ->label('P&L treatment')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, BookCategory $record): string =>
                        BookCategory::REPORT_TREATMENTS[
                            $state ?: $record->effectiveReportTreatment()
                        ] ?? 'Excluded from P&L'
                    )
                    ->color(fn (?string $state, BookCategory $record): string => match (
                        $state ?: $record->effectiveReportTreatment()
                    ) {
                        'revenue' => 'success',
                        'capital_asset' => 'info',
                        'loan_finance' => 'warning',
                        'distribution_equity' => 'warning',
                        'tax_ato' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('report_order')
                    ->label('Report order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('default_gst_treatment')
                    ->label('GST')
                    ->boolean()
                    ->getStateUsing(fn (BookCategory $record): bool =>
                        $record->default_gst_treatment === 'gst_applicable'
                    )
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                TextColumn::make('default_business_use_percentage')
                    ->label('Bus. use')
                    ->formatStateUsing(fn ($state): string =>
                        number_format((float) $state, 0) . '%'
                    ),

                IconColumn::make('active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->native(false)
                    ->options([
                        'income' => 'Income',
                        'expense' => 'Expense',
                        'other' => 'Other',
                    ]),

                SelectFilter::make('report_treatment')
                    ->label('P&L treatment')
                    ->native(false)
                    ->options(BookCategory::REPORT_TREATMENTS),

                TernaryFilter::make('active')
                    ->label('Active')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->native(false)
                    ->falseLabel('Inactive only'),
            ])
            ->defaultSort('report_order')
            ->reorderable('report_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookCategories::route('/'),
            'create' => CreateBookCategory::route('/create'),
            'edit' => EditBookCategory::route('/{record}/edit'),
        ];
    }
}
