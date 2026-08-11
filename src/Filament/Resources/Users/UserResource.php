<?php

namespace Housetrevethan\FilamentAuth\Filament\Resources\Users;

use Housetrevethan\FilamentAuth\Filament\Resources\Users\Pages\CreateUser;
use Housetrevethan\FilamentAuth\Filament\Resources\Users\Pages\EditUser;
use Housetrevethan\FilamentAuth\Filament\Resources\Users\Pages\ListUsers;
use Housetrevethan\FilamentAuth\Filament\Resources\Users\Schemas\UserForm;
use Housetrevethan\FilamentAuth\Filament\Resources\Users\Tables\UsersTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    /**
     * Consuming apps always follow the App\Models\User convention.
     * Stored as a string so no import is needed in the package.
     */
    protected static ?string $model = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModel(): string
    {
        return config('filament-auth.user_model', 'App\Models\User');
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit'   => EditUser::route('/{record}/edit'),
        ];
    }
}
