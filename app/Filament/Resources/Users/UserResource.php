<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Resource;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use App\Support\FilamentAccess;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Аккаунты';

    protected static ?string $modelLabel = 'Аккаунт';

    protected static ?string $pluralModelLabel = 'Аккаунты';

    protected static ?string $recordTitleAttribute = 'name';

    protected static UnitEnum | string | null $navigationGroup = 'Системная таблица';

    public static function canAccess(): bool
    {
        return FilamentAccess::isSuperAdmin() && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
        ];
    }
}
