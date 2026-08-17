<?php

namespace App\Filament\Resources\BookBankAccounts;

use App\Filament\Resources\BookBankAccounts\Pages\CreateBookBankAccount;
use App\Filament\Resources\BookBankAccounts\Pages\EditBookBankAccount;
use App\Filament\Resources\BookBankAccounts\Pages\ListBookBankAccounts;
use App\Models\BookBankAccount;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookBankAccountResource extends Resource
{
    protected static ?string $model = BookBankAccount::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;
    protected static ?string $navigationLabel = 'Bank Accounts';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'books/settings/bank-accounts';

    public static function canAccess(): bool { return auth()->user()?->canAccessBooks() ?? false; }

    public static function getEloquentQuery(): Builder
    {
        $business = app(BookContext::class)->current();
        return parent::getEloquentQuery()->where('book_business_id', $business?->id ?? 0);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('book_business_id')
                ->default(fn () => app(BookContext::class)->current()?->id)
                ->required(),
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('institution')->maxLength(255),
            TextInput::make('account_name')->label('Account name')->maxLength(255),
            TextInput::make('last_four')->label('Last 4 digits')->maxLength(4),
            Toggle::make('active')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('institution'),
            TextColumn::make('last_four')->label('Last 4'),
            IconColumn::make('active')
                ->label('Active')
                ->boolean()
                ->trueColor('success')
                ->falseColor('danger'),
        ])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookBankAccounts::route('/'),
            'create' => CreateBookBankAccount::route('/create'),
            'edit' => EditBookBankAccount::route('/{record}/edit'),
        ];
    }
}
