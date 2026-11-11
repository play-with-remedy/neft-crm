<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Evenings\EveningResource;
use App\Models\Evening;
use App\Support\FilamentAccess;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestEvenings extends TableWidget
{
    public static function canView(): bool
    {
        return FilamentAccess::isSuperAdmin();
    }

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
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', ' ') . ' BYN')
                    ->alignEnd(),

                TextColumn::make('dashboard_profit')
                    ->label('Прибыль')
                    ->state(
                        fn (Evening $record): float => (float) $record->participants_sum_paid_amount
                            - (float) $record->staff_sum_salary
                            - (float) $record->expenses_sum_amount
                            - (float) $record->other_expenses
                    )
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', ' ') . ' BYN')
                    ->color(fn ($state): string => (float) $state < 0 ? 'danger' : 'success')
                    ->weight('bold')
                    ->alignEnd(),
            ])
            ->recordUrl(fn (Evening $record): string => EveningResource::getUrl('view', ['record' => $record]))
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5]);
    }
}
