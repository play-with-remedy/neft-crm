<?php

namespace App\Filament\Resources\Evenings;

use App\Filament\Resources\Evenings\Pages\CreateEvening;
use App\Filament\Resources\Evenings\Pages\EditEvening;
use App\Filament\Resources\Evenings\Pages\ListEvenings;
use App\Filament\Resources\Evenings\Pages\ViewEvening;
use App\Filament\Resources\Evenings\Schemas\EveningForm;
use App\Filament\Resources\Evenings\Schemas\EveningInfolist;
use App\Filament\Resources\Evenings\Tables\EveningsTable;
use App\Models\Evening;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class EveningResource extends Resource
{
    protected static ?string $model = \App\Models\Evening::class;
    protected static ?string $navigationLabel = 'Вечера';
    protected static ?string $modelLabel = 'Вечер';
    protected static ?string $pluralModelLabel = 'Вечера';

    protected static UnitEnum|string|null $navigationGroup = 'Клуб';
    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function form(Schema $schema): Schema
    {
        return EveningForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EveningInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EveningsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvenings::route('/'),
            'create' => CreateEvening::route('/create'),
            'view' => ViewEvening::route('/{record}'),
            'edit' => EditEvening::route('/{record}/edit'),
        ];
    }
}
