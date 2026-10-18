<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Filament\Forms\Components\PhoneInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('received_at')
                    ->label('Дата')
                    ->default(now())
                    ->required(),

                TextInput::make('name')
                    ->label('Имя')
                    ->maxLength(255),

                PhoneInput::make(),

                TextInput::make('telegram')
                    ->label('Telegram')
                    ->maxLength(255),

                TextInput::make('source_channel')
                    ->label('Откуда пришел')
                    ->maxLength(255),

                TextInput::make('stage')
                    ->label('Стадия работы')
                    ->maxLength(255),
            ])
            ->columns(2);
    }
}
