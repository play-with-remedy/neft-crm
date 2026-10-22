<?php

namespace App\Filament\Resources\EveningLockPeriods\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class EveningLockPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('starts_on')
                    ->label('Дата начала')
                    ->native()
                    ->required(),

                DatePicker::make('ends_on')
                    ->label('Дата окончания')
                    ->native()
                    ->afterOrEqual('starts_on')
                    ->required()
                    ->validationMessages([
                        'after_or_equal' => 'Дата окончания не может быть раньше даты начала.',
                    ]),

                Textarea::make('reason')
                    ->label('Причина')
                    ->placeholder('Необязательно')
                    ->maxLength(255)
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
