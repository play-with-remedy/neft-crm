<?php

namespace App\Filament\Resources\Evenings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EveningForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основное')
                    ->schema([
                        DateTimePicker::make('played_at')
                            ->label('Дата проведения')
                            ->required(),

                        Select::make('location_id')
                            ->label('Локация')
                            ->relationship('location', 'name')
                            ->preload()
                            ->required(),

                        Select::make('evening_type_id')
                            ->label('Тип вечера')
                            ->relationship('eveningType', 'name')
                            ->preload()
                            ->nullable(),

                        Select::make('project_id')
                            ->label('Проект')
                            ->relationship('project', 'name')
                            ->preload()
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Расходы')
                    ->schema([
                        Repeater::make('expenses')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Select::make('expense_category_id')
                                    ->label('Статья расходов')
                                    ->relationship('category', 'name')
                                    ->preload()
                                    ->required(),

                                TextInput::make('amount')
                                    ->label('Сумма')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->columnSpanFull()
                            ->addActionLabel('Добавить расход')
                            ->deleteAction(
                                fn ($action) => $action
                                    ->icon('heroicon-m-x-mark')
                                    ->label('')
                            ),
                    ])
                    ->columnSpanFull(),

                Section::make('Команда вечера')
                    ->schema([
                        Repeater::make('staff')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Select::make('host_id')
                                    ->label('Человек')
                                    ->relationship('host', 'nickname')
                                    ->preload()
                                    ->required(),

                                Select::make('role')
                                    ->label('Роль')
                                    ->options([
                                        'host' => 'Ведущий',
                                        'manager' => 'Админ',
                                    ])
                                    ->required(),

                                TextInput::make('salary')
                                    ->label('Зарплата')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required(),
                            ])
                            ->columns(3)
                            ->columnSpanFull()
                            ->deleteAction(
                                fn ($action) => $action
                                    ->icon('heroicon-m-x-mark')
                                    ->label('')
                            ),
                    ])
                    ->columnSpanFull(),

                Section::make('Участники')
                    ->schema([
                        Repeater::make('participants')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Select::make('player_id')
                                    ->label('Игрок')
                                    ->relationship('player', 'nickname')
                                    ->searchable()
                                    ->preload(false)
                                    ->required(),

                                Select::make('payment_type_id')
                                    ->label('Тип оплаты')
                                    ->relationship('paymentType', 'type')
                                    ->required()
                                    ->preload(),

                                TextInput::make('paid_amount')
                                    ->label('Оплата')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),

                                Toggle::make('is_new_player')
                                    ->label('Новый игрок')
                                    ->default(false)
                                    ->inline(false),

                                Toggle::make('is_full_payment')
                                    ->label('Полная оплата')
                                    ->default(true)
                                    ->inline(false),

                                Textarea::make('note')
                                    ->label('Примечание')
                                    ->rows(2)
                                    ->placeholder('Комментарий по оплате / игроку')
                                    ->columnSpan(5),
                            ])
                            ->columns(5)
                            ->columnSpanFull()
                            ->deleteAction(
                                fn ($action) => $action
                                    ->icon('heroicon-m-x-mark')
                                    ->label('')
                            ),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}