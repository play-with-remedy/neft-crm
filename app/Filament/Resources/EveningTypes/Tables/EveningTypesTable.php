<?php

namespace App\Filament\Resources\EveningTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EveningTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Описание')
                    ->limit(50)
                    ->wrap(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Редактировать')
                    ->modalHeading('Редактирование типа проекта')
                    ->modalSubmitActionLabel('Сохранить')
                    ->modalCancelActionLabel('Отмена'),
                DeleteAction::make()
                    ->label('Удалить')
                    ->modalHeading('Удаление типа проекта')
                    ->modalDescription('Вы уверены, что хотите удалить тип проект?')
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
