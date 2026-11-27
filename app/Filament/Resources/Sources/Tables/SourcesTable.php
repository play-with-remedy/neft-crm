<?php

namespace App\Filament\Resources\Sources\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class SourcesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Источник')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Редактировать')
                    ->iconButton()
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('Изменить источник')
                    ->modalHeading('Редактирование источника')
                    ->modalSubmitActionLabel('Сохранить')
                    ->modalCancelActionLabel('Отмена'),
                DeleteAction::make()
                    ->label('Удалить')
                    ->iconButton()
                    ->icon('heroicon-o-trash')
                    ->tooltip('Удалить источник')
                    ->modalHeading('Удаление источинка')
                    ->modalDescription('Вы уверены, что хотите удалить источник?')
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
