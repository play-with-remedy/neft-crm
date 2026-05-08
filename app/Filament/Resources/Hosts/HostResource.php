<?php

namespace App\Filament\Resources\Hosts;

use App\Filament\Resources\Hosts\Pages\ListHosts;
use App\Filament\Resources\Hosts\Schemas\HostForm;
use App\Filament\Resources\Hosts\Tables\HostsTable;
use App\Models\Host;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use UnitEnum;

class HostResource extends Resource
{
    protected static ?string $model = Host::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $navigationLabel = 'Админы';
    protected static ?string $modelLabel = 'Админ';
    protected static ?string $pluralModelLabel = 'Админы';
    protected static ?string $recordTitleAttribute = 'nickname';
    protected static UnitEnum|string|null $navigationGroup = 'Служебные данные';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return HostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HostsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHosts::route('/'),
        ];
    }
}
