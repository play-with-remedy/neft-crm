<?php

namespace App\Filament\Resources\Evenings\Tables;

use App\Filament\Resources\Evenings\Actions\EveningLockActions;
use App\Models\Evening;
use App\Services\EveningLockService;
use App\Support\EveningCsvExporter;
use App\Support\FilamentAccess;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
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
                    ->icon(fn (Evening $record): ?string => app(EveningLockService::class)->isLocked($record)
                        ? 'heroicon-s-lock-closed'
                        : null)
                    ->iconPosition('after')
                    ->iconColor('warning')
                    ->tooltip(fn (Evening $record): ?string => app(EveningLockService::class)->description($record))
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
                    ->label('Оплата')
                    ->numeric(decimalPlaces: 0)
                    ->suffix(' BYN')
                    ->alignCenter()
                    ->sum('participants', 'paid_amount'),

                TextColumn::make('staff_sum_salary')
                    ->label('ЗП Команды')
                    ->numeric(decimalPlaces: 0)
                    ->suffix(' BYN')
                    ->alignCenter()
                    ->sum('staff', 'salary'),

                TextColumn::make('expenses_sum_amount')
                    ->label('Расходы')
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
                        $participants = (int) $record->participants_sum_paid_amount;
                        $staff = (int) $record->staff_sum_salary;
                        $expenses = (int) $record->expenses_sum_amount;

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
            ->defaultSort('played_at', 'desc')
            ->recordActions([
                ViewAction::make()->label('Детали'),
                EditAction::make()->label('Изменить'),
                Action::make('editPlaceholder')
                    ->label('Изменить')
                    ->icon('heroicon-o-pencil-square')
                    ->link()
                    ->disabled()
                    ->extraAttributes([
                        'style' => 'visibility: hidden;',
                    ])
                    ->visible(fn (Evening $record): bool => ! FilamentAccess::isSuperAdmin()
                        && app(EveningLockService::class)->isLocked($record)),
                EveningLockActions::lock(iconOnly: true),
                EveningLockActions::unlock(iconOnly: true),
                Action::make('lockPlaceholder')
                    ->label('Место для блокировки')
                    ->icon('heroicon-m-lock-closed')
                    ->iconButton()
                    ->size('sm')
                    ->disabled()
                    ->extraAttributes([
                        'style' => 'visibility: hidden;',
                    ])
                    ->visible(fn (Evening $record): bool => ! FilamentAccess::isSuperAdmin()
                        || (app(EveningLockService::class)->isLocked($record) && blank($record->locked_at))),
                Action::make('export')
                    ->label('Экспорт')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->iconButton()
                    ->size('sm')
                    ->tooltip('Экспорт вечера')
                    ->authorize(fn (): bool => FilamentAccess::isSuperAdmin())
                    ->action(fn (Evening $record) => EveningCsvExporter::download($record)),
                Action::make('exportPlaceholder')
                    ->label('Место для экспорта')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->iconButton()
                    ->size('sm')
                    ->disabled()
                    ->extraAttributes([
                        'style' => 'visibility: hidden;',
                    ])
                    ->visible(fn (): bool => ! FilamentAccess::isSuperAdmin()),
            ]);
    }
}
