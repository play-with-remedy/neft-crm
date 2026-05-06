<?php

namespace App\Filament\Resources\Hosts\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nickname')
                    ->label('Никнейм')
                    ->required(),

                TextInput::make('first_name')
                    ->label('Имя'),

                TextInput::make('last_name')
                    ->label('Фамилия'),
            ]);
    }
}
