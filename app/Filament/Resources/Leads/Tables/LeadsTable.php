<?php

namespace App\Filament\Resources\Leads\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('received_at')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),

                TextColumn::make('telegram')
                    ->label('Telegram')
                    ->searchable(),

                TextColumn::make('source_channel')
                    ->label('Откуда пришел')
                    ->searchable(),

                TextColumn::make('stage')
                    ->label('Стадия работы')
                    ->searchable(),
            ])
            ->defaultSort('received_at', 'desc')
            ->recordActions([
                EditAction::make()
                    ->label('Редактировать')
                    ->modalHeading('Редактирование лида')
                    ->modalSubmitActionLabel('Сохранить')
                    ->modalCancelActionLabel('Отмена'),
                DeleteAction::make()
                    ->label('Удалить')
                    ->modalHeading('Удаление лида')
                    ->modalDescription('Вы уверены, что хотите удалить лид?')
                    ->modalSubmitActionLabel('Удалить')
                    ->modalCancelActionLabel('Отмена'),
            ]);
    }
}
