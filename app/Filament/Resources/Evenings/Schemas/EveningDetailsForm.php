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
                ->required($forSchedule)
                ->validationMessages([
                    'required' => 'Выберите проект.',
                    'exists' => 'Выбранный проект не найден.',
                ]),

            Select::make('evening_type_id')
                ->label('Тип вечера')
                ->relationship('eveningType', 'name')
                ->preload()
                ->required($forSchedule)
                ->validationMessages([
                    'required' => 'Выберите тип вечера.',
                    'exists' => 'Выбранный тип вечера не найден.',
                ]),

            DateTimePicker::make('played_at')
                ->label('Дата и время проведения')
                ->native(! $forSchedule)
                ->locale('ru')
                ->displayFormat('d.m.Y H:i')
                ->seconds(false)
                ->minDate('2000-01-01 00:00:00')
                ->maxDate('2100-12-31 23:59:59')
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
                ->suffix('руб.')
                ->validationMessages([
                    'numeric' => 'Укажите стоимость числом.',
                    'min' => 'Стоимость не может быть отрицательной.',
                    'max' => 'Стоимость не может превышать 99 999 999,99 руб.',
                ])
                ->nullable(),

            TextInput::make('address')
                ->label('Адрес')
                ->maxLength(500)
                ->validationMessages(['max' => 'Адрес не должен быть длиннее 500 символов.'])
                ->columnSpanFull()
                ->nullable(),
        ];
    }
}
