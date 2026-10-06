<?php

namespace App\Filament\Resources\Certificates\Schemas;

use App\Models\Certificate;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CertificateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('purchased_at')
                    ->label('Дата покупки')
                    ->default(now())
                    ->maxDate(now())
                    ->beforeOrEqual(now()->toDateString())
                    ->required(),

                Select::make('type')
                    ->label('Вид')
                    ->options(Certificate::typeOptions())
                    ->native(false)
                    ->required(),

                Select::make('player_id')
                    ->label('Ник покупателя')
                    ->relationship('purchaser', 'nickname')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(),

                DatePicker::make('used_at')
                    ->label('Дата использования')
                    ->minDate(fn (Get $get): ?string => $get('purchased_at'))
                    ->maxDate(now())
                    ->afterOrEqual('purchased_at')
                    ->beforeOrEqual(now()->toDateString()),
            ])
            ->columns(2);
    }
}
