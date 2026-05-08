<?php

namespace App\Filament\Resources\Evenings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EveningsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name')
                    ->label('Проект')
                    ->alignCenter()
                    ->searchable(),

                TextColumn::make('eveningType.name')
                    ->label('Тип вечера')
                    ->alignCenter(),

                TextColumn::make('played_at')
                    ->label('Дата')
                    ->date('M d, Y')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('participants_sum_paid_amount')
                    ->label('Оплата игроков')
                    ->numeric(decimalPlaces: 0)
                    ->suffix(' BYN')
                    ->alignCenter()
                    ->sum('participants', 'paid_amount'),

                TextColumn::make('staff_sum_salary')
                    ->label('Затраты Команды')
                    ->numeric(decimalPlaces: 0)
                    ->suffix(' BYN')
                    ->alignCenter()
                    ->sum('staff', 'salary'),

                TextColumn::make('expenses_sum_amount')
                    ->label('Прочие расходы')
                    ->numeric(decimalPlaces: 0)
                    ->suffix(' BYN')
                    ->alignCenter()
                    ->sum('expenses', 'amount'),

                TextColumn::make('profit')
                    ->label('Выручка')
                    ->numeric(decimalPlaces: 0)
                    ->alignCenter()
                    ->suffix(' BYN')
                    ->state(function ($record) {
                        $participants = $record->participants()->sum('paid_amount');
                        $staff = $record->staff()->sum('salary');
                        $expenses = $record->expenses()->sum('amount');

                        return $participants - $staff - $expenses;
                    }),
            ])
            ->defaultSort('played_at', 'desc')
            ->recordActions([
                ViewAction::make()->label('Детали'),
                EditAction::make()->label('Изменить'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}