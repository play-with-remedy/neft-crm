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
                        TextEntry::make('project.name')
                            ->label('Проект')
                            ->placeholder('Не указан'),

                        TextEntry::make('eveningType.name')
                            ->label('Тип вечера')
                            ->placeholder('Не указан'),

                        TextEntry::make('played_at')
                            ->label('Дата проведения')
                            ->date('d.m.Y'),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),

                Section::make('Итоги вечера')
                    ->schema([
                        TextEntry::make('total_paid_amount')
                            ->label('Выручка')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' BYN'),

                        TextEntry::make('staff_salary_total')
                            ->label('Зарплаты команды')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' BYN'),

                        TextEntry::make('expenses_total')
                            ->label('Расходы')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' BYN'),

                        TextEntry::make('profit')
                            ->label('Прибыль')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' BYN'),

                        TextEntry::make('players_count')
                            ->label('Всего игроков'),

                        TextEntry::make('new_players_count')
                            ->label('Новички'),

                    ])
                    ->columns(4)
                    ->columnSpanFull(),

                Section::make('Расходы')
                    ->schema([
                        RepeatableEntry::make('expenses')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('category.name')
                                    ->label('Статья расходов'),

                                TextEntry::make('amount')
                                    ->label('Сумма')
                                    ->numeric(decimalPlaces: 2)
                                    ->suffix(' BYN'),
                            ])
                            ->columns(2),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),

                Section::make('Команда вечера')
                    ->schema([
                        RepeatableEntry::make('staff')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('host.nickname')
                                    ->label('Человек'),

                                TextEntry::make('role')
                                    ->label('Роль')
                                    ->formatStateUsing(fn ($state) => match ($state) {
                                        'host' => 'Ведущий',
                                        'admin' => 'Админ',
                                        'manager' => 'Менеджер',
                                        'supervisor' => 'Супервайзер',
                                        default => $state,
                                    }),

                                TextEntry::make('salary')
                                    ->label('Зарплата')
                                    ->numeric(decimalPlaces: 2)
                                    ->suffix(' BYN'),
                            ])
                            ->columns(3),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),

                Section::make('Оплаты по типам')
                    ->schema([
                        RepeatableEntry::make('payments_by_type')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Тип оплаты'),

                                TextEntry::make('amount')
                                    ->label('Сумма')
                                    ->numeric(decimalPlaces: 2)
                                    ->suffix(' BYN'),
                            ])
                            ->columns(2),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),

                Section::make(fn ($record) => 'Участники: ' . ($record?->participants?->count() ?? 0))
                    ->schema([
                        RepeatableEntry::make('participants')
                            ->hiddenLabel()
                            ->getStateUsing(fn ($record) => $record->participants()
                            ->with(['player', 'payments.paymentType'])
                                ->leftJoin('players', 'players.id', '=', 'evening_participants.player_id')
                                ->orderByDesc('evening_participants.paid_amount')
                                ->orderBy('players.nickname')
                                ->select('evening_participants.*')
                                ->get())
                            ->schema([
                                TextEntry::make('player.nickname')
                                    ->label('Игрок'),

                                TextEntry::make('payment_summary')
                                    ->label('Оплаты')
                                    ->wrap(),

                                TextEntry::make('paid_amount')
                                    ->label('Оплата')
                                    ->numeric(decimalPlaces: 2)
                                    ->suffix(' BYN'),

                                TextEntry::make('is_new_player')
                                    ->label('Тип игрока')
                                    ->formatStateUsing(fn ($state) => $state ? 'Новый' : 'Обычный'),

                                TextEntry::make('note')
                                    ->label('Примечание')
                                    ->placeholder('—'),
                            ])
                            ->columns(5),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
    }
}
