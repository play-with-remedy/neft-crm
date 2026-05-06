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
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),

                TextColumn::make('played_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('location.name')
                    ->label('Локация')
                    ->searchable(),

                TextColumn::make('eveningType.name')
                    ->label('Тип вечера')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('participants_count')
                    ->label('Игроков')
                    ->counts('participants'),

                TextColumn::make('participants_sum_paid_amount')
                    ->label('Выручка')
                    ->sum('participants', 'paid_amount'),
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