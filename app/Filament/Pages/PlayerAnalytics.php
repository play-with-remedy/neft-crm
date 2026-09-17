<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Players\PlayerResource;
use App\Models\EveningParticipant;
use App\Models\EveningType;
use App\Models\Player;
use App\Models\Project;
use App\Support\PlayerAnalyticsXlsxExporter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use UnitEnum;

class PlayerAnalytics extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?string $slug = 'player-analytics';

    protected static ?string $navigationLabel = 'Аналитика игроков';

    protected static ?string $title = 'Аналитика игроков';

    protected static UnitEnum|string|null $navigationGroup = 'Отчеты';

    protected static ?int $navigationSort = 35;

    protected string $view = 'filament.pages.player-analytics';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportTopPlayers')
                ->label('Выгрузить топ игроков')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->schema([
                    TextInput::make('limit')
                        ->label('Количество игроков')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(10000)
                        ->default(100)
                        ->required(),
                ])
                ->modalHeading('Выгрузить топ игроков в Excel')
                ->modalSubmitActionLabel('Выгрузить')
                ->action(fn (array $data) => PlayerAnalyticsXlsxExporter::download(
                    $this->topPlayersForExport((int) $data['limit']),
                    $this->appliedFiltersLabel(),
                )),
        ];
    }

    public function table(Table $table): Table
    {
        [$activityPeriodFrom, $activityPeriodUntil] = $this->activityPeriod();

        return $table
            ->query(function () use ($activityPeriodFrom, $activityPeriodUntil): Builder {
                $visitFilter = $this->visitFilter();
                $hasVisitFilters = $this->hasVisitFilters();

                return Player::query()
                    ->select('players.*')
                    ->with('source:id,name')
                    ->when($hasVisitFilters, fn (Builder $query): Builder => $query->whereHas('participations', $visitFilter))
                    ->withCount([
                        'participations as visits_count' => $visitFilter,
                        'participations as lifetime_visits_count',
                        'participations as recent_visits_count' => fn (Builder $query): Builder => $query->whereHas(
                            'evening',
                            fn (Builder $query): Builder => $query->whereBetween(
                                'played_at',
                                [$activityPeriodFrom, $activityPeriodUntil],
                            ),
                        ),
                    ])
                    ->withSum(['participations as ltv_total' => $visitFilter], 'paid_amount')
                    ->addSelect([
                        'first_visit_at' => $this->visitDateSubquery('MIN', $visitFilter),
                        'last_visit_at' => $this->visitDateSubquery('MAX', $visitFilter),
                        'lifetime_first_visit_at' => $this->visitDateSubquery('MIN'),
                        'lifetime_last_visit_at' => $this->visitDateSubquery('MAX'),
                    ]);
            })
            ->columns([
                TextColumn::make('nickname')
                    ->label('Никнейм')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Player $record): string => PlayerResource::getUrl('view', ['record' => $record])),

                TextColumn::make('status')
                    ->label('Статус')
                    ->state(fn (Player $record): string => $this->funnelStatusKey((int) $record->lifetime_visits_count))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('lifetime_visits_count', $direction))
                    ->formatStateUsing(fn (string $state): HtmlString => new HtmlString(
                        view(
                            'filament.pages.partials.player-status-badge',
                            [
                                'stageKey' => $state,
                                'label' => $this->funnelStatusLabel($state),
                            ],
                        )->render()
                    )),

                TextColumn::make('activity_status')
                    ->label('Статус активности')
                    ->state(fn (Player $record): string => $record->activity_status_label)
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderByRaw(
                            "CASE
                                WHEN manual_activity_status = ? THEN 3
                                WHEN recent_visits_count >= ? THEN 2
                                ELSE 1
                            END {$direction}",
                            [
                                Player::MANUAL_ACTIVITY_STATUS_SEASON_PLAYER,
                                Player::CLUB_PLAYER_VISITS_THRESHOLD,
                            ],
                        ))
                    ->formatStateUsing(fn (string $state): HtmlString => new HtmlString(
                        view('filament.pages.partials.player-activity-status-badge', [
                            'label' => $state,
                        ])->render()
                    )),

                TextColumn::make('source.name')
                    ->label('Источник')
                    ->placeholder('—'),

                TextColumn::make('first_visit_at')
                    ->label('Первый визит')
                    ->date('d.m.Y')
                    ->sortable()
                    ->extraCellAttributes(['class' => 'player-analytics-centered-cell'])
                    ->placeholder('—'),

                TextColumn::make('last_visit_at')
                    ->label('Последний визит')
                    ->date('d.m.Y')
                    ->sortable()
                    ->extraCellAttributes(['class' => 'player-analytics-centered-cell'])
                    ->placeholder('—'),

                TextColumn::make('visits_count')
                    ->label('Визитов')
                    ->numeric(decimalPlaces: 0)
                    ->sortable()
                    ->extraCellAttributes(['class' => 'player-analytics-centered-cell']),

                TextColumn::make('ltv_total')
                    ->label('LTV всего')
                    ->state(fn (Player $record): float => (float) $record->ltv_total)
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', ' ').' BYN')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('ltv_total', $direction))
                    ->extraCellAttributes(['class' => 'player-analytics-centered-cell']),

                TextColumn::make('duration')
                    ->label('Продолжительность')
                    ->state(fn (Player $record): string => $this->formatDuration($record))
                    ->extraCellAttributes(['class' => 'player-analytics-centered-cell']),
            ])
            ->filters([
                Filter::make('played_at')
                    ->label('Период посещения')
                    ->form([
                        DatePicker::make('from')
                            ->label('С даты'),
                        DatePicker::make('until')
                            ->label('По дату'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $this->filterByVisitAttributes(
                        $query,
                    ))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'С даты: '.Carbon::parse($data['from'])->format('d.m.Y');
                        }

                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'По дату: '.Carbon::parse($data['until'])->format('d.m.Y');
                        }

                        return $indicators;
                    }),

                SelectFilter::make('evening_type_id')
                    ->label('Тип вечера')
                    ->placeholder('Все типы вечеров')
                    ->options(fn (): array => EveningType::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->preload()
                    ->query(fn (Builder $query, array $data): Builder => $this->filterByVisitAttributes(
                        $query,
                    )),

                SelectFilter::make('project_id')
                    ->label('Проект')
                    ->placeholder('Все проекты')
                    ->options(fn (): array => Project::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->preload()
                    ->query(fn (Builder $query, array $data): Builder => $this->filterByVisitAttributes(
                        $query,
                    )),

                SelectFilter::make('funnel_status')
                    ->label('Статус')
                    ->placeholder('Все статусы')
                    ->options([
                        'none' => 'Без статуса',
                        'new' => 'Новый',
                        'returned' => 'Вернулся',
                        'interested' => 'Заинтересован',
                        'engaged' => 'Вовлечён',
                        'contender' => 'Претендент',
                        'active' => 'Активный',
                        'regular' => 'Постоянный',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $this->filterByFunnelStatus(
                        $query,
                        $data['value'] ?? null,
                    )),

                SelectFilter::make('activity_status')
                    ->label('Статус активности')
                    ->placeholder('Все статусы активности')
                    ->options([
                        'season_player' => 'Игрок сезона',
                        'club_player' => 'Клубный игрок',
                        'club_guest' => 'Гость клуба',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $this->filterByActivityStatus(
                        $query,
                        $data['value'] ?? null,
                    )),
            ])
            ->defaultSort('visits_count', 'desc');
    }

    private function filterByVisitAttributes(Builder $query): Builder
    {
        // The unified visit scope is applied while the computed columns are built.
        return $query;
    }

    /** @return Collection<int, Player> */
    public function topPlayersForExport(int $limit): Collection
    {
        return $this->getFilteredTableQuery()
            ->reorder()
            ->orderByDesc('ltv_total')
            ->orderBy('players.nickname')
            ->limit($limit)
            ->get();
    }

    public function appliedFiltersLabel(): string
    {
        $filters = [];
        $period = $this->getTableFilterState('played_at') ?? [];

        if (filled($period['from'] ?? null)) {
            $filters[] = 'С даты: '.Carbon::parse($period['from'])->format('d.m.Y');
        }

        if (filled($period['until'] ?? null)) {
            $filters[] = 'По дату: '.Carbon::parse($period['until'])->format('d.m.Y');
        }

        $eveningTypeId = $this->getTableFilterState('evening_type_id')['value'] ?? null;
        if (filled($eveningTypeId)) {
            $filters[] = 'Тип вечера: '.(EveningType::query()->find($eveningTypeId)?->name ?? '—');
        }

        $projectId = $this->getTableFilterState('project_id')['value'] ?? null;
        if (filled($projectId)) {
            $filters[] = 'Проект: '.(Project::query()->find($projectId)?->name ?? '—');
        }

        $funnelStatus = $this->getTableFilterState('funnel_status')['value'] ?? null;
        if (filled($funnelStatus)) {
            $filters[] = 'Статус: '.$this->funnelStatusLabel($funnelStatus);
        }

        $activityStatus = $this->getTableFilterState('activity_status')['value'] ?? null;
        if (filled($activityStatus)) {
            $filters[] = 'Статус активности: '.$this->activityStatusLabel($activityStatus);
        }

        if (filled($this->getTableSearch())) {
            $filters[] = 'Поиск: '.$this->getTableSearch();
        }

        return $filters === [] ? 'Фильтры: не применены' : 'Фильтры: '.implode('; ', $filters);
    }

    private function hasVisitFilters(): bool
    {
        $period = $this->getTableFilterState('played_at') ?? [];

        return filled($period['from'] ?? null)
            || filled($period['until'] ?? null)
            || filled($this->getTableFilterState('evening_type_id')['value'] ?? null)
            || filled($this->getTableFilterState('project_id')['value'] ?? null);
    }

    private function visitFilter(): \Closure
    {
        $period = $this->getTableFilterState('played_at') ?? [];
        $from = $period['from'] ?? null;
        $until = $period['until'] ?? null;
        $eveningTypeId = $this->getTableFilterState('evening_type_id')['value'] ?? null;
        $projectId = $this->getTableFilterState('project_id')['value'] ?? null;

        return fn (Builder $query): Builder => $query->whereHas(
            'evening',
            fn (Builder $query): Builder => $query
                ->when($from, fn (Builder $query, string $date): Builder => $query->whereDate('played_at', '>=', $date))
                ->when($until, fn (Builder $query, string $date): Builder => $query->whereDate('played_at', '<=', $date))
                ->when($eveningTypeId, fn (Builder $query, $id): Builder => $query->where('evening_type_id', $id))
                ->when($projectId, fn (Builder $query, $id): Builder => $query->where('project_id', $id)),
        );
    }

    private function visitDateSubquery(string $aggregate, ?\Closure $visitFilter = null): Builder
    {
        $query = EveningParticipant::query()
            ->join('evenings', 'evenings.id', '=', 'evening_participants.evening_id')
            ->whereColumn('evening_participants.player_id', 'players.id');

        if ($visitFilter !== null) {
            $query->where($visitFilter);
        }

        return $query->selectRaw("{$aggregate}(evenings.played_at)");
    }

    private function filterByFunnelStatus(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            'none' => $query->whereDoesntHave('participations'),
            'new' => $query->has('participations', '=', 1),
            'returned' => $query->has('participations', '=', 2),
            'interested' => $query->has('participations', '=', 3),
            'engaged' => $query->has('participations', '=', 4),
            'contender' => $query
                ->has('participations', '>=', 5)
                ->has('participations', '<=', 9),
            'active' => $query
                ->has('participations', '>=', 10)
                ->has('participations', '<=', 20),
            'regular' => $query->has('participations', '>=', 21),
            default => $query,
        };
    }

    private function filterByActivityStatus(Builder $query, ?string $status): Builder
    {
        [$from, $until] = $this->activityPeriod();
        $recentVisits = fn (Builder $query): Builder => $query
            ->whereHas('evening', fn (Builder $query): Builder => $query
                ->whereBetween('played_at', [$from, $until]));

        return match ($status) {
            'season_player' => $query->where(
                'manual_activity_status',
                Player::MANUAL_ACTIVITY_STATUS_SEASON_PLAYER,
            ),
            'club_player' => $query
                ->whereNull('manual_activity_status')
                ->whereHas(
                    'participations',
                    $recentVisits,
                    '>=',
                    Player::CLUB_PLAYER_VISITS_THRESHOLD,
                ),
            'club_guest' => $query
                ->whereNull('manual_activity_status')
                ->whereHas(
                    'participations',
                    $recentVisits,
                    '<',
                    Player::CLUB_PLAYER_VISITS_THRESHOLD,
                ),
            default => $query,
        };
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function activityPeriod(): array
    {
        return [now()->startOfMonth()->subMonthsNoOverflow(4), now()];
    }

    private function formatDuration(Player $player): string
    {
        if (! $player->lifetime_first_visit_at || ! $player->lifetime_last_visit_at) {
            return '—';
        }

        $interval = Carbon::parse($player->lifetime_first_visit_at)
            ->startOfDay()
            ->diff(Carbon::parse($player->lifetime_last_visit_at)->startOfDay());

        $parts = [];

        if ($interval->y > 0) {
            $parts[] = $interval->y.' '.$this->russianPlural($interval->y, 'год', 'года', 'лет');
        }

        if ($interval->m > 0) {
            $parts[] = $interval->m.' '.$this->russianPlural($interval->m, 'месяц', 'месяца', 'месяцев');
        }

        if ($interval->d > 0 || $parts === []) {
            $parts[] = $interval->d.' '.$this->russianPlural($interval->d, 'день', 'дня', 'дней');
        }

        return implode(' ', $parts);
    }

    private function funnelStatusKey(int $visits): string
    {
        return match (true) {
            $visits >= 21 => 'regular',
            $visits >= 10 => 'active',
            $visits >= 5 => 'contender',
            $visits === 4 => 'engaged',
            $visits === 3 => 'interested',
            $visits === 2 => 'returned',
            $visits === 1 => 'new',
            default => 'none',
        };
    }

    private function funnelStatusLabel(string $status): string
    {
        return match ($status) {
            'new' => 'Новый',
            'returned' => 'Вернулся',
            'interested' => 'Заинтересован',
            'engaged' => 'Вовлечён',
            'contender' => 'Претендент',
            'active' => 'Активный',
            'regular' => 'Постоянный',
            default => '—',
        };
    }

    private function activityStatusLabel(string $status): string
    {
        return match ($status) {
            'season_player' => 'Игрок сезона',
            'club_player' => 'Клубный игрок',
            'club_guest' => 'Гость клуба',
            default => '—',
        };
    }

    private function russianPlural(int $number, string $one, string $few, string $many): string
    {
        $number = abs($number) % 100;
        $lastDigit = $number % 10;

        if ($number > 10 && $number < 20) {
            return $many;
        }

        return match (true) {
            $lastDigit === 1 => $one,
            $lastDigit >= 2 && $lastDigit <= 4 => $few,
            default => $many,
        };
    }
}
