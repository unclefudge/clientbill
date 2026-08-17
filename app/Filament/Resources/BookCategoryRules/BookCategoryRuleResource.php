<?php

namespace App\Filament\Resources\BookCategoryRules;

use App\Filament\Resources\BookCategoryRules\Pages\CreateBookCategoryRule;
use App\Filament\Resources\BookCategoryRules\Pages\EditBookCategoryRule;
use App\Filament\Resources\BookCategoryRules\Pages\ListBookCategoryRules;
use App\Models\BookCategory;
use App\Models\BookCategoryRule;
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

class BookCategoryRuleResource extends Resource
{
    protected static ?string $model = BookCategoryRule::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;
    protected static ?string $navigationLabel = 'Category Rules';
    protected static ?string $modelLabel = 'category rule';
    protected static ?string $pluralModelLabel = 'category rules';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'books/settings/category-rules';

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

            Select::make('book_category_id')
                ->label('Category')
                ->native(false)
                ->searchable()
                ->options(fn (): array => static::categoryOptions())
                ->required(),

            Select::make('match_type')
                ->label('Match type')
                ->native(false)
                ->options([
                    'contains' => 'Contains',
                    'starts_with' => 'Starts with',
                    'exact' => 'Exact',
                ])
                ->default('contains')
                ->required(),

            TextInput::make('match_value')
                ->label('Bank description match')
                ->helperText('Active rules are trusted: matching bank rows are automatically confirmed and marked Ready. Examples: DIGITALOCEAN, SPINTEL, BELONG, or a longer exact bank description.')
                ->required()
                ->maxLength(255),

            Toggle::make('active')
                ->inline(false)
                ->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->label('Category')
                    ->formatStateUsing(fn (?string $state, BookCategoryRule $record): string => $state
                        ? $state . ' (' . ucfirst((string) $record->category?->type) . ')'
                        : '—')
                    ->sortable(),
                TextColumn::make('match_value')
                    ->label('Bank description match')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('match_type')
                    ->label('Match')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'starts_with' => 'Starts with',
                        'exact' => 'Exact',
                        default => 'Contains',
                    }),
                TextColumn::make('times_confirmed')
                    ->label('Confirmed')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('book_category_id')
                    ->label('Category')
                    ->options(fn (): array => static::categoryOptions()),
                SelectFilter::make('match_type')
                    ->label('Match type')
                    ->options([
                        'contains' => 'Contains',
                        'starts_with' => 'Starts with',
                        'exact' => 'Exact',
                    ]),
                TernaryFilter::make('active')
                    ->label('Active')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->defaultSort('category.name');
    }

    protected static function categoryOptions(): array
    {
        return BookCategory::query()
            ->where('book_business_id', app(BookContext::class)->current()?->id ?? 0)
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (BookCategory $category): array => [
                $category->id => $category->name . ' (' . ucfirst((string) $category->type) . ')',
            ])
            ->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookCategoryRules::route('/'),
            'create' => CreateBookCategoryRule::route('/create'),
            'edit' => EditBookCategoryRule::route('/{record}/edit'),
        ];
    }
}
