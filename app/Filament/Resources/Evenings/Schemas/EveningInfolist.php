<?php

namespace App\Filament\Resources\Evenings\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EveningInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основное')
                    ->schema([
                        TextEntry::make('played_at')
                            ->label('Дата проведения')
                            ->dateTime(),

                        TextEntry::make('location.name')
                            ->label('Локация'),

                        TextEntry::make('eveningType.name')
                            ->label('Тип вечера')
                            ->placeholder('Не указан'),

                        TextEntry::make('project.name')
                            ->label('Проект')
                            ->placeholder('Не указан'),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),

                Section::make('Итоги вечера')
                    ->schema([
                        TextEntry::make('total_paid_amount')
                            ->label('Выручка')
                            ->suffix(' BYN'),

                        TextEntry::make('staff_salary_total')
                            ->label('Зарплаты команды')
                            ->suffix(' BYN'),

                        TextEntry::make('expenses_total')
                            ->label('Расходы')
                            ->suffix(' BYN'),

                        TextEntry::make('profit')
                            ->label('Прибыль')
                            ->suffix(' BYN'),

                        TextEntry::make('players_count')
                            ->label('Всего игроков'),

                        TextEntry::make('new_players_count')
                            ->label('Новички'),

                        TextEntry::make('full_payment_players_count')
                            ->label('Полная оплата'),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),

                Section::make('Расходы')
                    ->schema([
                        RepeatableEntry::make('expenses')
                            ->label('')
                            ->schema([
                                TextEntry::make('category.name')
                                    ->label('Статья расходов'),

                                TextEntry::make('amount')
                                    ->label('Сумма')
                                    ->suffix(' BYN'),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),

                Section::make('Команда вечера')
                    ->schema([
                        RepeatableEntry::make('staff')
                            ->label('')
                            ->schema([
                                TextEntry::make('host.nickname')
                                    ->label('Человек'),

                                TextEntry::make('role')
                                    ->label('Роль')
                                    ->formatStateUsing(fn ($state) => match ($state) {
                                        'host' => 'Ведущий',
                                        'manager' => 'Админ',
                                        default => $state,
                                    }),

                                TextEntry::make('salary')
                                    ->label('Зарплата')
                                    ->suffix(' BYN'),
                            ])
                            ->columns(3),
                    ])
                    ->columnSpanFull(),

                Section::make('Оплаты по типам')
                    ->schema([
                        RepeatableEntry::make('payments_by_type')
                            ->label('')
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Тип оплаты'),

                                TextEntry::make('amount')
                                    ->label('Сумма')
                                    ->suffix(' BYN'),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),

                Section::make('Участники')
                    ->schema([
                        RepeatableEntry::make('participants')
                            ->label('')
                            ->schema([
                                TextEntry::make('player.nickname')
                                    ->label('Игрок'),

                                TextEntry::make('paymentType.type')
                                    ->label('Тип оплаты'),

                                TextEntry::make('paid_amount')
                                    ->label('Оплата')
                                    ->suffix(' BYN'),

                                TextEntry::make('is_new_player')
                                    ->label('Тип игрока')
                                    ->formatStateUsing(fn ($state) => $state ? 'Новый' : 'Обычный'),

                                TextEntry::make('is_full_payment')
                                    ->label('Оплата')
                                    ->formatStateUsing(fn ($state) => $state ? 'Полная' : 'Частичная'),

                                TextEntry::make('note')
                                    ->label('Примечание')
                                    ->placeholder('—'),
                            ])
                            ->columns(6),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}