<?php

namespace App\Filament\Resources\Locations;

use App\Filament\Resources\Locations\Pages\ListLocations;
use App\Filament\Resources\Locations\Schemas\LocationForm;
use App\Filament\Resources\Locations\Tables\LocationsTable;
use App\Filament\Resources\Resource;
use App\Models\Location;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $slug = 'addresses';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Локации';

    protected static ?string $modelLabel = 'локация';

    protected static ?string $pluralModelLabel = 'локации';

    protected static UnitEnum|string|null $navigationGroup = 'Служебные данные';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return LocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LocationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListLocations::route('/')];
    }
}
