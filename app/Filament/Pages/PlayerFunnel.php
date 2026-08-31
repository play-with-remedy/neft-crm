<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Players\PlayerResource;
use App\Models\EveningParticipant;
use App\Models\Player;
use App\Models\Source;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use UnitEnum;

class PlayerFunnel extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFunnel;

    protected static ?string $slug = 'funnel';

    protected static ?string $navigationLabel = 'Воронка';

    protected static UnitEnum|string|null $navigationGroup = 'Отчеты';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Воронка';

    protected string $view = 'filament.pages.player-funnel';

    /** @var array<int, string> */
    #[Url(as: 'months', history: true)]
    public array $periods = [];

    public string $pendingPeriodFrom = '';

    public string $pendingPeriodUntil = '';

    public string $lossPlayersHeading = '';

    /** @var array<int, array{nickname: string, first_visit_at: string, evenings_count: int, url: string}> */
    public array $lossPlayers = [];

    /** @var array{stages: array<int, array{key: string, label: string, range: string}>, rows: array<int, array{month: string, label: string, counts: array<string, int>}>} */
    public array $playerDynamics = [];

    /** @var array<int, string> */
    public array $attractionSources = [];

    private ?EloquentCollection $periodPlayersCache = null;

    public function mount(): void
    {
        $this->periods = $this->normalizePeriods($this->periods);
        $this->setPendingPeriodRange();

        $this->playerDynamics = $this->getPlayerDynamicsStats();
        $this->attractionSources = Source::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function applyPeriodRange(): void
    {
        $this->resetErrorBag();

        if (! $this->isValidPeriod($this->pendingPeriodFrom)
            || ! $this->isValidPeriod($this->pendingPeriodUntil)) {
            $this->addError('periodRange', 'Укажите начальный и конечный месяц.');

            return;
        }

        $start = Carbon::createFromFormat('!Y-m', $this->pendingPeriodFrom)->startOfMonth();
        $end = Carbon::createFromFormat('!Y-m', $this->pendingPeriodUntil)->startOfMonth();

        if ($start->greaterThan($end)) {
            $this->addError('periodRange', 'Начальный месяц не может быть позже конечного.');

            return;
        }

        if ($start->diffInMonths($end) >= 12) {
            $this->addError('periodRange', 'Можно выбрать период не более 12 месяцев.');

            return;
        }

        $periods = [];

        for ($month = $start->copy(); $month->lessThanOrEqualTo($end); $month->addMonth()) {
            $periods[] = $month->format('Y-m');
        }

        $this->periods = array_reverse($periods);

        $this->periodPlayersCache = null;
    }

    /**
     * @return array{
     *     total: int,
     *     stages: array<int, array{key: string, label: string, range: string, minimum: int, count: int, percentage: float}>,
     *     losses: array<int, array{key: string, label: string, transition: string, count: int, percentage: float}>
     * }
     */
    public function getFunnelStats(): array
    {
        $players = $this->getPeriodPlayers();

        $visitCounts = $players->map(
            fn (Player $player): int => max(1, (int) $player->evenings_count)
        );

        $stages = collect($this->stageDefinitions())
            ->map(function (array $stage) use ($players, $visitCounts): array {
                $count = $visitCounts
                    ->filter(fn (int $count): bool => $count >= $stage['minimum'])
                    ->count();

                return [
                    ...$stage,
                    'count' => $count,
                    'percentage' => $players->isEmpty()
                        ? 0
                        : round(($count / $players->count()) * 100, 1),
                ];
            });

        $lossLabels = [
            'После 1-го визита',
            'После 2-го визита',
            'После 3-го визита',
            'После 4-го визита',
            'После 5–9 визитов',
            'После 10–20 визитов',
        ];

        $losses = $stages
            ->take(count($lossLabels))
            ->values()
            ->map(function (array $stage, int $index) use ($lossLabels, $stages): array {
                $nextStage = $stages->values()->get($index + 1);
                $lostCount = max(0, $stage['count'] - $nextStage['count']);

                return [
                    'key' => $stage['key'],
                    'label' => $lossLabels[$index],
                    'transition' => $stage['label'].' → '.$nextStage['label'],
                    'count' => $lostCount,
                    'percentage' => $stage['count'] === 0
                        ? 0
                        : round(($lostCount / $stage['count']) * 100, 1),
                ];
            });

        return [
            'total' => $players->count(),
            'stages' => $stages->values()->all(),
            'losses' => $losses->all(),
        ];
    }

    public function openLossPlayers(string $stageKey): void
    {
        $ranges = [
            'new' => ['minimum' => 1, 'maximum' => 1, 'label' => 'После 1-го визита'],
            'returned' => ['minimum' => 2, 'maximum' => 2, 'label' => 'После 2-го визита'],
            'interested' => ['minimum' => 3, 'maximum' => 3, 'label' => 'После 3-го визита'],
            'engaged' => ['minimum' => 4, 'maximum' => 4, 'label' => 'После 4-го визита'],
            'contender' => ['minimum' => 5, 'maximum' => 9, 'label' => 'После 5–9 визитов'],
            'active' => ['minimum' => 10, 'maximum' => 20, 'label' => 'После 10–20 визитов'],
        ];

        if (! array_key_exists($stageKey, $ranges)) {
            return;
        }

        $range = $ranges[$stageKey];

        $this->lossPlayers = $this->getPeriodPlayers()
            ->sortBy('nickname')
            ->filter(function (Player $player) use ($range): bool {
                $visits = max(1, (int) $player->evenings_count);

                return $visits >= $range['minimum'] && $visits <= $range['maximum'];
            })
            ->map(fn (Player $player): array => [
                'nickname' => $player->nickname,
                'first_visit_at' => $player->first_visit_at?->format('d.m.Y') ?? '—',
                'evenings_count' => max(1, (int) $player->evenings_count),
                'url' => PlayerResource::getUrl('view', ['record' => $player]),
            ])
            ->values()
            ->all();

        $this->lossPlayersHeading = $range['label'];
        $this->dispatch('open-modal', id: 'loss-players-modal');
    }

    /**
     * @return array{
     *     stages: array<int, array{key: string, label: string, range: string}>,
     *     rows: array<int, array{name: string, counts: array<string, int>}>
     * }
     */
    public function getAttractionSourceStats(): array
    {
        $stages = collect($this->stageDefinitions());

        $playersBySource = $this->getPeriodPlayers()
            ->whereNotNull('source_id')
            ->groupBy('source_id');

        $rows = collect($this->attractionSources)
            ->map(function (string $sourceName, int|string $sourceId) use ($playersBySource, $stages): array {
                $visitCounts = $playersBySource
                    ->get((int) $sourceId, collect())
                    ->map(fn (Player $player): int => max(1, (int) $player->evenings_count));

                return [
                    'name' => $sourceName,
                    'counts' => $stages->mapWithKeys(fn (array $stage): array => [
                        $stage['key'] => $visitCounts
                            ->filter(fn (int $count): bool => $count >= $stage['minimum'])
                            ->count(),
                    ])->all(),
                ];
            })
            ->sortByDesc(fn (array $row): int => $row['counts']['new'])
            ->values()
            ->all();

        return [
            'stages' => $stages
                ->map(fn (array $stage): array => [
                    'key' => $stage['key'],
                    'label' => $stage['label'],
                    'range' => $stage['range'],
                ])
                ->all(),
            'rows' => $rows,
        ];
    }

    /**
     * @return array<int, array{
     *     key: string,
     *     label: string,
     *     average_days: float|null,
     *     median_days: float|null
     * }>
     */
    public function getTransitionTimingStats(): array
    {
        $milestones = [1, 2, 3, 4, 5, 10, 21];

        $rankedVisits = EveningParticipant::query()
            ->join('evenings', 'evenings.id', '=', 'evening_participants.evening_id')
            ->join('players', 'players.id', '=', 'evening_participants.player_id')
            ->selectRaw('evening_participants.player_id')
            ->selectRaw('evenings.played_at')
            ->selectRaw(
                'ROW_NUMBER() OVER (PARTITION BY evening_participants.player_id ORDER BY evenings.played_at, evening_participants.id) AS visit_number'
            );

        $this->applyPeriodFilter($rankedVisits, 'players.first_visit_at');

        $milestoneSelects = collect($milestones)
            ->map(fn (int $visit): string =>
                "MAX(CASE WHEN visit_number = {$visit} THEN played_at END) AS visit_{$visit}"
            )
            ->implode(', ');

        $players = DB::query()
            ->fromSub($rankedVisits, 'ranked_visits')
            ->select('player_id')
            ->selectRaw($milestoneSelects)
            ->groupBy('player_id')
            ->get();

        return collect([
            ['key' => 'new_returned', 'label' => 'Новый → Вернулся', 'from' => 1, 'to' => 2],
            ['key' => 'returned_interested', 'label' => 'Вернулся → Заинтересован', 'from' => 2, 'to' => 3],
            ['key' => 'interested_engaged', 'label' => 'Заинтересован → Вовлечён', 'from' => 3, 'to' => 4],
            ['key' => 'engaged_contender', 'label' => 'Вовлечён → Претендент', 'from' => 4, 'to' => 5],
            ['key' => 'contender_active', 'label' => 'Претендент → Активный', 'from' => 5, 'to' => 10],
            ['key' => 'active_regular', 'label' => 'Активный → Постоянный', 'from' => 10, 'to' => 21],
        ])->map(function (array $transition) use ($players): array {
            $durations = $players
                ->filter(fn (object $player): bool =>
                    filled($player->{"visit_{$transition['from']}"})
                    && filled($player->{"visit_{$transition['to']}"})
                )
                ->map(fn (object $player): float => Carbon::parse(
                    $player->{"visit_{$transition['from']}"}
                )->diffInDays(Carbon::parse($player->{"visit_{$transition['to']}"})));

            return [
                'key' => $transition['key'],
                'label' => $transition['label'],
                'average_days' => $durations->isEmpty() ? null : round($durations->average(), 1),
                'median_days' => $this->median($durations->all()),
            ];
        })->all();
    }

    /**
     * @return array{
     *     stages: array<int, array{key: string, label: string, range: string}>,
     *     rows: array<int, array{month: string, label: string, counts: array<string, int>}>
     * }
     */
    private function getPlayerDynamicsStats(): array
    {
        $stages = collect($this->stageDefinitions());
        [$visitYearExpression, $visitMonthExpression] = match (DB::connection()->getDriverName()) {
            'sqlite' => [
                "CAST(strftime('%Y', players.first_visit_at) AS INTEGER)",
                "CAST(strftime('%m', players.first_visit_at) AS INTEGER)",
            ],
            default => [
                'EXTRACT(YEAR FROM players.first_visit_at)',
                'EXTRACT(MONTH FROM players.first_visit_at)',
            ],
        };

        $participationCounts = EveningParticipant::query()
            ->selectRaw('player_id, COUNT(*) AS evenings_count')
            ->groupBy('player_id');

        $query = Player::query()
            ->leftJoinSub($participationCounts, 'player_visits', function ($join): void {
                $join->on('player_visits.player_id', '=', 'players.id');
            })
            ->whereNotNull('players.first_visit_at')
            ->selectRaw("{$visitYearExpression} AS visit_year")
            ->selectRaw("{$visitMonthExpression} AS visit_month")
            ->selectRaw('COUNT(*) AS new_count');

        $stages
            ->where('key', '!=', 'new')
            ->each(function (array $stage) use ($query): void {
                $query->selectRaw(
                    "SUM(CASE WHEN COALESCE(player_visits.evenings_count, 0) >= ? THEN 1 ELSE 0 END) AS {$stage['key']}_count",
                    [$stage['minimum']],
                );
            });

        $rows = $query
            ->groupByRaw("{$visitYearExpression}, {$visitMonthExpression}")
            ->orderByDesc('visit_year')
            ->orderByDesc('visit_month')
            ->get()
            ->map(function (Player $row) use ($stages): array {
                $month = sprintf('%04d-%02d', (int) $row->visit_year, (int) $row->visit_month);

                return [
                    'month' => $month,
                    'label' => Str::ucfirst(
                        Carbon::createFromFormat('!Y-m', $month)
                            ->locale('ru')
                            ->translatedFormat('F Y')
                    ),
                    'counts' => $stages->mapWithKeys(fn (array $stage): array => [
                        $stage['key'] => (int) $row->getAttribute("{$stage['key']}_count"),
                    ])->all(),
                ];
            })
            ->all();

        return [
            'stages' => $stages
                ->map(fn (array $stage): array => [
                    'key' => $stage['key'],
                    'label' => $stage['label'],
                    'range' => $stage['range'],
                ])
                ->all(),
            'rows' => $rows,
        ];
    }

    private function stageDefinitions(): array
    {
        return [
            ['key' => 'new', 'label' => 'Новый', 'range' => '1 визит', 'minimum' => 1],
            ['key' => 'returned', 'label' => 'Вернулся', 'range' => '2 визита', 'minimum' => 2],
            ['key' => 'interested', 'label' => 'Заинтересован', 'range' => '3 визита', 'minimum' => 3],
            ['key' => 'engaged', 'label' => 'Вовлечён', 'range' => '4 визита', 'minimum' => 4],
            ['key' => 'contender', 'label' => 'Претендент', 'range' => '5–9 визитов', 'minimum' => 5],
            ['key' => 'active', 'label' => 'Активный', 'range' => '10–20 визитов', 'minimum' => 10],
            ['key' => 'regular', 'label' => 'Постоянный', 'range' => '21+ визит', 'minimum' => 21],
        ];
    }

    /** @param array<int, float> $values */
    private function median(array $values): ?float
    {
        if ($values === []) {
            return null;
        }

        sort($values, SORT_NUMERIC);
        $middle = intdiv(count($values), 2);

        return round(count($values) % 2 === 1
            ? $values[$middle]
            : ($values[$middle - 1] + $values[$middle]) / 2, 1);
    }

    private function getPeriodPlayers(): EloquentCollection
    {
        if ($this->periodPlayersCache !== null) {
            return $this->periodPlayersCache;
        }

        $query = Player::query()
            ->select([
                'players.id',
                'players.nickname',
                'players.first_visit_at',
                'players.source_id',
            ])
            ->withCount(['participations as evenings_count']);

        $this->applyPeriodFilter($query, 'players.first_visit_at');

        return $this->periodPlayersCache = $query->get();
    }

    /**
     * @param  array<int, mixed>  $periods
     * @return array<int, string>
     */
    private function normalizePeriods(array $periods): array
    {
        $normalized = collect($periods)
            ->filter(fn (mixed $period): bool => is_string($period)
                && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period) === 1)
            ->unique()
            ->sortDesc()
            ->take(12)
            ->values()
            ->all();

        return $normalized === [] ? [now()->format('Y-m')] : $normalized;
    }

    private function setPendingPeriodRange(): void
    {
        $periods = collect($this->periods)->sort()->values();

        $this->pendingPeriodFrom = (string) $periods->first();
        $this->pendingPeriodUntil = (string) $periods->last();
    }

    private function isValidPeriod(string $period): bool
    {
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period) === 1;
    }

    /**
     * @return array<int, array{start: Carbon, end: Carbon}>
     */
    private function selectedPeriodRanges(): array
    {
        $months = collect($this->normalizePeriods($this->periods))
            ->map(fn (string $period): Carbon => Carbon::createFromFormat('!Y-m', $period)->startOfMonth())
            ->sortBy(fn (Carbon $month): int => $month->timestamp)
            ->values();

        $ranges = [];

        foreach ($months as $month) {
            $lastIndex = array_key_last($ranges);

            if ($lastIndex !== null && $ranges[$lastIndex]['end']->equalTo($month)) {
                $ranges[$lastIndex]['end'] = $month->copy()->addMonth();

                continue;
            }

            $ranges[] = [
                'start' => $month->copy(),
                'end' => $month->copy()->addMonth(),
            ];
        }

        return $ranges;
    }

    private function applyPeriodFilter(Builder $query, string $column): void
    {
        $ranges = $this->selectedPeriodRanges();

        $query->where(function (Builder $query) use ($column, $ranges): void {
            foreach ($ranges as $range) {
                $query->orWhere(function (Builder $query) use ($column, $range): void {
                    $query
                        ->where($column, '>=', $range['start']->toDateString())
                        ->where($column, '<', $range['end']->toDateString());
                });
            }
        });
    }
}
