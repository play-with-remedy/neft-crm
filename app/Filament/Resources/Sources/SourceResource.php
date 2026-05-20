<?php

namespace App\Filament\Resources\Sources;

use App\Filament\Resources\Sources\Pages\ListSources;
use App\Filament\Resources\Sources\Schemas\SourceForm;
use App\Filament\Resources\Sources\Tables\SourcesTable;
use App\Models\Source;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SourceResource extends Resource
{
    protected static ?string $model = Source::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'Источники';
    protected static ?string $modelLabel = 'Источник';
    protected static ?string $pluralModelLabel = 'Источники';
    protected static ?string $recordTitleAttribute = 'name';
    protected static UnitEnum|string|null $navigationGroup = 'Служебные данные';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return SourceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SourcesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSources::route('/'),
        ];
    }
}
