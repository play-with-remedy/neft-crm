<?php

namespace App\Filament\Resources\Certificates\Tables;

use App\Models\Certificate;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CertificatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('№')
                    ->rowIndex(),

                TextColumn::make('purchased_at')
                    ->label('Дата покупки')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Вид')
                    ->formatStateUsing(fn (string $state): string => Certificate::typeOptions()[$state] ?? $state)
                    ->badge(),

                TextColumn::make('purchaser.nickname')
                    ->label('Ник покупателя')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('used_at')
                    ->label('Дата использования')
                    ->date('d.m.Y')
                    ->placeholder('Не использован')
                    ->sortable(),
            ])
            ->defaultSort('purchased_at', 'desc')
            ->recordActions([
                EditAction::make()
                    ->label('Редактировать')
                    ->modalHeading('Редактирование сертификата')
                    ->modalSubmitActionLabel('Сохранить')
                    ->modalCancelActionLabel('Отмена'),
                DeleteAction::make()
                    ->label('Удалить')
                    ->modalHeading('Удаление сертификата')
                    ->modalDescription('Вы уверены, что хотите удалить сертификат?')
                    ->modalSubmitActionLabel('Удалить')
                    ->modalCancelActionLabel('Отмена'),
            ]);
    }
}
