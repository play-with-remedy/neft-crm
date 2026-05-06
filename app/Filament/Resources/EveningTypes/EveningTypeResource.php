<?php

namespace App\Filament\Resources\EveningTypes;

use App\Filament\Resources\EveningTypes\Pages\CreateEveningType;
use App\Filament\Resources\EveningTypes\Pages\EditEveningType;
use App\Filament\Resources\EveningTypes\Pages\ListEveningTypes;
use App\Filament\Resources\EveningTypes\Pages\ViewEveningType;
use App\Filament\Resources\EveningTypes\Schemas\EveningTypeForm;
use App\Filament\Resources\EveningTypes\Schemas\EveningTypeInfolist;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';
    protected static UnitEnum|string|null $navigationGroup = 'Служебные данные';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Название')
                ->required()
                ->maxLength(255),

            Textarea::make('description')
                ->label('Описание')
                ->rows(3),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EveningTypeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')
                ->label('Название')
                ->searchable()
                ->sortable(),

            TextColumn::make('created_at')
                ->label('Создан')
                ->dateTime('d.m.Y H:i')
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
            'index' => ListEveningTypes::route('/'),
            'create' => CreateEveningType::route('/create'),
            'view' => ViewEveningType::route('/{record}'),
            'edit' => EditEveningType::route('/{record}/edit'),
        ];
    }
}
