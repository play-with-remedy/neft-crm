<?php

namespace App\Filament\Resources\EveningTypes\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EveningTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required(),

                Textarea::make('description')
                    ->label('Описание')
                    ->rows(4),
            ]);
    }
}