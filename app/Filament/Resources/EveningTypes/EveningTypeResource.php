<?php

namespace App\Filament\Resources\EveningTypes;

use App\Filament\Resources\EveningTypes\Pages\ListEveningTypes;
use App\Filament\Resources\EveningTypes\Schemas\EveningTypeForm;
use App\Filament\Resources\EveningTypes\Tables\EveningTypesTable;
use App\Models\EveningType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use UnitEnum;

class EveningTypeResource extends Resource
{
    protected static ?string $model = EveningType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMoon;
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $navigationLabel = 'Типы вечера';
    protected static ?string $modelLabel = 'Тип вечера';
    protected static ?string $pluralModelLabel = 'Типы вечера';
    protected static UnitEnum|string|null $navigationGroup = 'Служебные данные';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return EveningTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
         return EveningTypesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEveningTypes::route('/'),
        ];
    }
}
