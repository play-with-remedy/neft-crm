<?php

namespace App\Filament\Resources\Hosts;

use App\Filament\Resources\Hosts\Pages\CreateHost;
use App\Filament\Resources\Hosts\Pages\EditHost;
use App\Filament\Resources\Hosts\Pages\ListHosts;
use App\Filament\Resources\Hosts\Pages\ViewHost;
use App\Filament\Resources\Hosts\Schemas\HostForm;
use App\Filament\Resources\Hosts\Schemas\HostInfolist;
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
    protected static ?string $navigationLabel = 'Ведущие';
    protected static ?string $modelLabel = 'Ведущий';
    protected static ?string $pluralModelLabel = 'Ведущие';
    protected static ?string $recordTitleAttribute = 'nickname';
    protected static UnitEnum|string|null $navigationGroup = 'Служебные данные';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return HostForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return HostInfolist::configure($schema);
    }

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('nickname')
                ->label('Никнейм')
                ->searchable()
                ->sortable(),

            TextColumn::make('first_name')
                ->label('Имя')
                ->searchable()
                ->sortable(),

            TextColumn::make('last_name')
                ->label('Фамилия')
                ->searchable()
                ->sortable(),
        ]);
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
            'index' => ListHosts::route('/'),
            'create' => CreateHost::route('/create'),
            'view' => ViewHost::route('/{record}'),
            'edit' => EditHost::route('/{record}/edit'),
        ];
    }
}
