<?php

namespace App\Filament\Resources\Locations\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('halls'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Локация')->searchable()->sortable(),
                TextColumn::make('address')->label('Адрес')->searchable()->wrap(),
                TextColumn::make('halls.name')->label('Залы')->searchable()->listWithLineBreaks(),
            ])
            ->recordActions([
                EditAction::make()->label('Изменить')->iconButton()->tooltip('Изменить локацию')
                    ->modalHeading('Изменить локацию')->modalWidth('3xl')
                    ->modalSubmitActionLabel('Сохранить')->modalCancelActionLabel('Отмена')
                    ->successNotificationTitle('Локация сохранена'),
                DeleteAction::make()->label('Удалить')->iconButton()->tooltip('Удалить локацию')
                    ->modalHeading('Удалить локацию?')
                    ->modalDescription('Локация и все её залы будут удалены.')
                    ->modalSubmitActionLabel('Удалить')->modalCancelActionLabel('Отмена')
                    ->successNotificationTitle('Локация удалена'),
            ])
            ->emptyStateHeading('Локации пока не добавлены')
            ->emptyStateDescription('Добавьте локацию, её адрес и залы.');
    }
}
