<?php

namespace App\Filament\Resources\EveningLockPeriods\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EveningLockPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('starts_on')
                    ->label('С даты')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('ends_on')
                    ->label('По дату')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('reason')
                    ->label('Причина')
                    ->placeholder('—')
                    ->wrap(),

                TextColumn::make('lockedBy.name')
                    ->label('Кто закрыл')
                    ->placeholder('—'),
            ])
            ->defaultSort('starts_on', 'desc')
            ->recordActions([
                EditAction::make()
                    ->label('Изменить'),

                DeleteAction::make()
                    ->label('Открыть период')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->modalHeading('Открыть период')
                    ->modalDescription('Блокировка всех вечеров в этом диапазоне будет снята. Индивидуальные блокировки сохранятся.')
                    ->modalSubmitActionLabel('Открыть период')
                    ->modalCancelActionLabel('Отмена'),
            ]);
    }
}
