<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Players\PlayerResource;
use App\Models\Evening;
use App\Models\EveningParticipant;
use App\Models\EveningType;
use App\Models\Player;
use App\Models\Project;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class PlayerRating extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $navigationLabel = 'Рейтинг игроков';

    protected static ?string $title = 'Рейтинг игроков';

    protected static UnitEnum | string | null $navigationGroup = 'Отчеты';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.pages.player-rating';

    public function getSubheading(): ?string
    {
        return 'Посещения и оплаты игроков за всё время';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getPlayersQuery())
            ->columns([
                TextColumn::make('position')
                    ->label('№')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('nickname')
                    ->label('Игрок')
                    ->searchable()
                    ->sortable()
                    ->url(
                        fn (Player $record): string => PlayerResource::getUrl(
                            'statistics',
                            ['record' => $record],
                        )
                    ),

                TextColumn::make('participations_count')
                    ->label('Посещений')
                    ->numeric(decimalPlaces: 0)
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('participations_sum_paid_amount')
                    ->label('Сумма оплаты')
                    ->formatStateUsing(
                        fn ($state): string => number_format((int) $state, 0, ',', ' ') . ' BYN'
                    )
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('statistics_first_visit')
                    ->label('Первый визит')
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder('—')
                    ->alignCenter(),

                TextColumn::make('statistics_last_visit')
                    ->label('Последний визит')
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder('—')
                    ->alignCenter(),

                TextColumn::make('activity_status')
                    ->label('Активность')
                    ->state(
                        fn (Player $record): string => $this->activityStatusLabel(
                            $record->activity_last_visit,
                        )
                    )
                    ->badge()
                    ->color(
                        fn (Player $record): string | array => $this->activityStatusColor(
                            $record->activity_last_visit,
                        )
                    )
                    ->tooltip(
                        fn (Player $record): string => $this->activityStatusTooltip(
                            $record->activity_last_visit,
                        )
                    )
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('project')
                    ->label('По проекту')
                    ->placeholder('Все проекты')
                    ->options(fn (): array => Project::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query): Builder => $query),

                SelectFilter::make('evening_type')
                    ->label('По типу')
                    ->placeholder('Все типы')
                    ->options(fn (): array => EveningType::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query): Builder => $query),

                SelectFilter::make('month')
                    ->label('По месяцу')
                    ->placeholder('Все месяцы')
                    ->options(fn (): array => $this->monthOptions())
                    ->query(fn (Builder $query): Builder => $query),

                SelectFilter::make('activity_status')
                    ->label('По активности')
                    ->placeholder('Все статусы')
                    ->options([
                        'active' => 'Активный — до 30 дней',
                        'pause' => 'Пауза — 31–60 дней',
                        'inactive' => 'Неактивный — 61–90 дней',
                        'dormant' => 'Давно не был — более 90 дней',
                    ])
                    ->query(fn (Builder $query): Builder => $query),
            ])
            ->filtersApplyAction(
                fn (Action $action): Action => $action->label('Применить')
            )
            ->filtersTriggerAction(function (Action $action) use ($table): Action {
                return $action
                    ->label('Фильтры')
                    ->modalCancelActionLabel('Закрыть')
                    ->extraModalFooterActions([
                        $table->getFiltersApplyAction()->close(),
                        Action::make('resetFilters')
                            ->label('Сбросить')
                            ->color('danger')
                            ->action('resetTableFiltersForm')
                            ->button(),
                    ]);
            })
            ->defaultSort('participations_sum_paid_amount', 'desc')
            ->emptyStateHeading('Игроки не найдены')
            ->emptyStateDescription('Измените параметры фильтра или добавьте посещения игроков.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([10, 25, 50, 100]);
    }

    private function getPlayersQuery(): Builder
    {
        $firstVisit = EveningParticipant::query()
            ->selectRaw('MIN(evenings.played_at)')
            ->join('evenings', 'evenings.id', '=', 'evening_participants.evening_id')
            ->whereColumn('evening_participants.player_id', 'players.id');

        $this->applyJoinedEveningFilters($firstVisit);

        $lastVisit = EveningParticipant::query()
            ->selectRaw('MAX(evenings.played_at)')
            ->join('evenings', 'evenings.id', '=', 'evening_participants.evening_id')
            ->whereColumn('evening_participants.player_id', 'players.id');

        $this->applyJoinedEveningFilters($lastVisit);

        $query = Player::query()
            ->whereHas(
                'participations',
                fn (Builder $query): Builder => $this->applyParticipationFilters($query),
            )
            ->withCount([
                'participations' => fn (Builder $query): Builder => $this->applyParticipationFilters($query),
            ])
            ->withSum([
                'participations' => fn (Builder $query): Builder => $this->applyParticipationFilters($query),
            ], 'paid_amount')
            ->addSelect([
                'statistics_first_visit' => $firstVisit,
                'statistics_last_visit' => $lastVisit,
                'activity_last_visit' => $this->actualLastVisitQuery(),
            ]);

        return $this->applyActivityStatusFilter($query);
    }

    private function actualLastVisitQuery(): Builder
    {
        return EveningParticipant::query()
            ->selectRaw('MAX(evenings.played_at)')
            ->join('evenings', 'evenings.id', '=', 'evening_participants.evening_id')
            ->whereColumn('evening_participants.player_id', 'players.id');
    }

    private function applyActivityStatusFilter(Builder $query): Builder
    {
        $status = $this->getTableFilterState('activity_status')['value'] ?? null;
        $thirtyDaysAgo = now()->startOfDay()->subDays(30);
        $sixtyDaysAgo = now()->startOfDay()->subDays(60);
        $ninetyDaysAgo = now()->startOfDay()->subDays(90);

        return match ($status) {
            'active' => $query->where(
                $this->actualLastVisitQuery(),
                '>=',
                $thirtyDaysAgo,
            ),
            'pause' => $query
                ->where($this->actualLastVisitQuery(), '>=', $sixtyDaysAgo)
                ->where($this->actualLastVisitQuery(), '<', $thirtyDaysAgo),
            'inactive' => $query
                ->where($this->actualLastVisitQuery(), '>=', $ninetyDaysAgo)
                ->where($this->actualLastVisitQuery(), '<', $sixtyDaysAgo),
            'dormant' => $query->where(
                $this->actualLastVisitQuery(),
                '<',
                $ninetyDaysAgo,
            ),
            default => $query,
        };
    }

    private function applyParticipationFilters(Builder $query): Builder
    {
        [$projectId, $eveningTypeId, $periodStart, $periodEnd] = $this->activeFilters();

        if (! $projectId && ! $eveningTypeId && ! $periodStart) {
            return $query;
        }

        return $query->whereHas('evening', function (Builder $query) use (
            $projectId,
            $eveningTypeId,
            $periodStart,
            $periodEnd,
        ): void {
            $query
                ->when($projectId, fn (Builder $query): Builder => $query->where('project_id', $projectId))
                ->when($eveningTypeId, fn (Builder $query): Builder => $query->where('evening_type_id', $eveningTypeId))
                ->when($periodStart, fn (Builder $query): Builder => $query
                    ->where('played_at', '>=', $periodStart)
                    ->where('played_at', '<', $periodEnd));
        });
    }

    private function applyJoinedEveningFilters(Builder $query): void
    {
        [$projectId, $eveningTypeId, $periodStart, $periodEnd] = $this->activeFilters();

        $query
            ->when($projectId, fn (Builder $query): Builder => $query->where('evenings.project_id', $projectId))
            ->when($eveningTypeId, fn (Builder $query): Builder => $query->where('evenings.evening_type_id', $eveningTypeId))
            ->when($periodStart, fn (Builder $query): Builder => $query
                ->where('evenings.played_at', '>=', $periodStart)
                ->where('evenings.played_at', '<', $periodEnd));
    }

    private function activeFilters(): array
    {
        $projectId = $this->getTableFilterState('project')['value'] ?? null;
        $eveningTypeId = $this->getTableFilterState('evening_type')['value'] ?? null;
        $month = $this->getTableFilterState('month')['value'] ?? null;

        $periodStart = filled($month)
            ? Carbon::createFromFormat('!Y-m', $month)->startOfMonth()
            : null;

        return [
            filled($projectId) ? (int) $projectId : null,
            filled($eveningTypeId) ? (int) $eveningTypeId : null,
            $periodStart,
            $periodStart?->copy()->addMonth(),
        ];
    }

    private function monthOptions(): array
    {
        return Evening::query()
            ->orderByDesc('played_at')
            ->pluck('played_at')
            ->map(fn ($date): Carbon => Carbon::parse($date)->startOfMonth())
            ->unique(fn (Carbon $date): string => $date->format('Y-m'))
            ->mapWithKeys(fn (Carbon $date): array => [
                $date->format('Y-m') => Str::ucfirst($date->locale('ru')->translatedFormat('F Y')),
            ])
            ->all();
    }

    private function activityStatusLabel($lastVisit): string
    {
        return match (true) {
            $this->daysSince($lastVisit) <= 30 => 'Активный',
            $this->daysSince($lastVisit) <= 60 => 'Пауза',
            $this->daysSince($lastVisit) <= 90 => 'Неактивный',
            default => 'Давно не был',
        };
    }

    private function activityStatusColor($lastVisit): string | array
    {
        return match (true) {
            $this->daysSince($lastVisit) <= 30 => 'success',
            $this->daysSince($lastVisit) <= 60 => 'warning',
            $this->daysSince($lastVisit) <= 90 => 'danger',
            default => Color::Purple,
        };
    }

    private function activityStatusTooltip($lastVisit): string
    {
        $date = Carbon::parse($lastVisit);
        $days = $this->daysSince($lastVisit);

        return 'Последний визит: '
            . $date->format('d.m.Y')
            . ' ('
            . $days
            . ' '
            . $this->dayWord($days)
            . ' назад)';
    }

    private function daysSince($lastVisit): int
    {
        return (int) Carbon::parse($lastVisit)
            ->startOfDay()
            ->diffInDays(now()->startOfDay());
    }

    private function dayWord(int $days): string
    {
        if (($days % 10 === 1) && ($days % 100 !== 11)) {
            return 'день';
        }

        if (
            in_array($days % 10, [2, 3, 4], true)
            && ! in_array($days % 100, [12, 13, 14], true)
        ) {
            return 'дня';
        }

        return 'дней';
    }
}
