<?php

namespace App\Filament\Resources\Evenings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class EveningsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('played_at')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('project.name')
                    ->label('Проект')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('eveningType.name')
                    ->label('Тип вечера')
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
            ->filters([
                Filter::make('played_at')
                    ->label('Период')
                    ->form([
                        DatePicker::make('from')
                            ->label('С даты'),

                        DatePicker::make('until')
                            ->label('По дату'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('played_at', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('played_at', '<=', $date),
                            );
                    }),

                SelectFilter::make('evening_type_id')
                    ->label('Тип вечера')
                    ->relationship('eveningType', 'name')
                    ->preload(),

                SelectFilter::make('project_id')
                    ->label('Проект')
                    ->relationship('project', 'name')
                    ->preload(),
            ])
            ->defaultSort('played_at', 'asc')
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