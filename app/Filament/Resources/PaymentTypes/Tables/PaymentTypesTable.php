<?php

namespace App\Filament\Resources\PaymentTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->searchable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Редактировать')
                    ->iconButton()
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('Изменить тип оплаты')
                    ->modalHeading('Редактирование типа оплаты')
                    ->modalSubmitActionLabel('Сохранить')
                    ->modalCancelActionLabel('Отмена'),
                DeleteAction::make()
                    ->label('Удалить')
                    ->iconButton()
                    ->icon('heroicon-o-trash')
                    ->tooltip('Удалить тип оплаты')
                    ->modalHeading('Удаление типа оплаты')
                    ->modalDescription('Вы уверены, что хотите удалить тип оплаты?')
                    ->modalSubmitActionLabel('Удалить')
                    ->modalCancelActionLabel('Отмена')
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
