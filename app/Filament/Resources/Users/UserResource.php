<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
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
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;
    protected static UnitEnum|string|null $navigationGroup = 'Admin';
    protected static ?int $navigationSort = 20;
    protected static ?string $navigationLabel = 'Users';

    public static function canAccess(): bool
    {
        return auth()->user()?->can_manage_users ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('login_code')
                ->label('Login code')
                ->helperText('Leave blank when editing to keep the existing code. The login screen uses this code to identify the user.')
                ->password()
                ->revealable()
                ->afterStateHydrated(fn (TextInput $component) => $component->state(null))
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state)),
            Select::make('default_area')
                ->label('Landing area')
                ->native(false)
                ->options(['billing' => 'Billing / ClientBill', 'books' => 'Books'])
                ->required(),
            Select::make('theme_mode')
                ->label('Preferred theme')
                ->native(false)
                ->options([
                    'light' => 'Light',
                    'dark' => 'Dark',
                    'system' => 'System',
                ])
                ->default('system')
                ->required(),
            Toggle::make('active')->default(true),
            Toggle::make('can_access_billing')->label('Can access Billing'),
            Toggle::make('can_manage_users')->label('Can manage users / businesses'),
            Select::make('bookBusinesses')
                ->label('Books businesses')
                ->relationship('bookBusinesses', 'name')
                ->multiple()
                ->preload()
                ->searchable()
                ->columnSpanFull(),
            Select::make('default_book_business_id')
                ->label('Default Books business')
                ->relationship('defaultBookBusiness', 'name')
                ->searchable()
                ->preload()
                ->nullable(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('email')->searchable(),
            TextColumn::make('default_area')->label('Starts in')->badge(),
            IconColumn::make('can_access_billing')->label('Billing')->boolean(),
            TextColumn::make('book_businesses_count')->counts('bookBusinesses')->label('Books'),
            IconColumn::make('active')->boolean(),
        ])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
