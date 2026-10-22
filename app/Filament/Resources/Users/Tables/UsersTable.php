<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->label('Роль')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->label())
                    ->color(fn (UserRole $state): string => $state === UserRole::SuperAdmin ? 'danger' : 'gray'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Редактировать')
                    ->modalHeading('Редактирование аккаунта')
                    ->modalSubmitActionLabel('Сохранить')
                    ->modalCancelActionLabel('Отмена'),
            ]);
    }
}
