<?php

namespace App\Filament\Resources\Locations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),

                TextInput::make('address')
                    ->label('Адрес')
                    ->required()
                    ->maxLength(255),

                TextInput::make('hall')
                    ->label('Зал')
                    ->maxLength(255),
            ]);
    }
}