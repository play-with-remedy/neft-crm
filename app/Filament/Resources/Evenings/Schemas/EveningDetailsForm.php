<?php

namespace App\Filament\Resources\Evenings\Schemas;

use App\Rules\UnlockedEveningDate;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class EveningDetailsForm
{
    public static function components(bool $forSchedule = false): array
    {
        return [
            Select::make('project_id')
                ->label('Проект')
                ->relationship('project', 'name')
                ->preload()
                ->required($forSchedule),

            Select::make('evening_type_id')
                ->label('Тип вечера')
                ->relationship('eveningType', 'name')
                ->preload()
                ->required($forSchedule),

            DateTimePicker::make('played_at')
                ->label('Дата и время проведения')
                ->native()
                ->seconds(false)
                ->minDate('2000-01-01 00:00:00')
                ->maxDate('2100-12-31 23:59:59')
                ->helperText('Время начала по Минску.')
                ->rule(new UnlockedEveningDate)
                ->required()
                ->validationMessages([
                    'required' => 'Укажите дату и время проведения.',
                    'date' => 'Укажите корректные дату и время.',
                    'after_or_equal' => 'Дата не может быть раньше 01.01.2000.',
                    'before_or_equal' => 'Дата не может быть позже 31.12.2100.',
                ]),

            TextInput::make('price')
                ->label('Стоимость')
                ->numeric()
                ->step(0.01)
                ->minValue(0)
                ->maxValue(99999999.99)
                ->suffix('BYN')
                ->nullable(),

            TextInput::make('address')
                ->label('Адрес')
                ->maxLength(500)
                ->columnSpanFull()
                ->nullable(),
        ];
    }
}
