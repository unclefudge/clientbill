<?php

namespace App\Filament\Resources\BookBusinesses;

use App\Filament\Resources\BookBusinesses\Pages\CreateBookBusiness;
use App\Filament\Resources\BookBusinesses\Pages\EditBookBusiness;
use App\Filament\Resources\BookBusinesses\Pages\ListBookBusinesses;
use App\Models\BookBusiness;
use BackedEnum;
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

class BookBusinessResource extends Resource
{
    protected static ?string $model = BookBusiness::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;
    protected static ?string $navigationLabel = 'Book Businesses';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'books/settings/businesses';

    public static function canAccess(): bool
    {
        return auth()->user()?->can_manage_users ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $userId = auth()->id();

        return parent::getEloquentQuery()
            ->whereHas('users', fn (Builder $query): Builder => $query->whereKey($userId));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('legal_name')->label('Legal name')->maxLength(255),
            TextInput::make('abn')->label('ABN')->maxLength(20),
            Toggle::make('gst_registered')->label('GST registered')->inline(false)->live(),
            TextInput::make('gst_rate')->label('GST rate')->numeric()->suffix('%')->default(10),
            Select::make('bas_frequency')
                ->native(false)
                ->label('BAS frequency')
                ->options(['quarterly' => 'Quarterly', 'monthly' => 'Monthly', 'annual' => 'Annual'])
                ->default('quarterly')
                ->required(),
            Select::make('users')
                ->native(false)
                ->label('Users with access')
                ->relationship('users', 'name')
                ->multiple()
                ->preload()
                ->searchable()
                ->columnSpanFull(),
            Toggle::make('active')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('abn')->label('ABN'),
            IconColumn::make('gst_registered')->label('GST')->boolean(),
            TextColumn::make('bas_frequency')->label('BAS')->badge(),
            TextColumn::make('users_count')->counts('users')->label('Users'),
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
            'index' => ListBookBusinesses::route('/'),
            'create' => CreateBookBusiness::route('/create'),
            'edit' => EditBookBusiness::route('/{record}/edit'),
        ];
    }
}
