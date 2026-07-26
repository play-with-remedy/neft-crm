<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Evenings\EveningResource;
use App\Models\Evening;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestEvenings extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Последние вечера')
            ->description('Краткий итог последних событий клуба')
            ->query(
                Evening::query()
                    ->with(['project', 'eveningType'])
                    ->withSum('participants', 'paid_amount')
                    ->withSum('staff', 'salary')
                    ->withSum('expenses', 'amount')
                    ->withCount('participants')
                    ->latest('played_at')
            )
            ->columns([
                TextColumn::make('played_at')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('project.name')
                    ->label('Проект')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('participants_count')
                    ->label('Игроков')
                    ->alignCenter(),

                TextColumn::make('participants_sum_paid_amount')
                    ->label('Выручка')
                    ->formatStateUsing(fn ($state): string => number_format((int) $state, 0, ',', ' ') . ' BYN')
                    ->alignEnd(),

                TextColumn::make('dashboard_profit')
                    ->label('Прибыль')
                    ->state(
                        fn (Evening $record): int => (int) $record->participants_sum_paid_amount
                            - (int) $record->staff_sum_salary
                            - (int) $record->expenses_sum_amount
                            - (int) $record->other_expenses
                    )
                    ->formatStateUsing(fn ($state): string => number_format((int) $state, 0, ',', ' ') . ' BYN')
                    ->color(fn ($state): string => (int) $state < 0 ? 'danger' : 'success')
                    ->weight('bold')
                    ->alignEnd(),
            ])
            ->recordUrl(fn (Evening $record): string => EveningResource::getUrl('view', ['record' => $record]))
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5]);
    }
}
