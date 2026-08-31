<?php

namespace App\Filament\Pages;

use App\Models\Evening;
use App\Models\EveningParticipant;
use App\Models\EveningType;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use UnitEnum;

class EventStatistics extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $slug = 'event-statistics';

    protected static ?string $navigationLabel = 'Статистика мероприятий';

    protected static ?string $title = 'Статистика мероприятий';

    protected static UnitEnum | string | null $navigationGroup = 'Отчеты';

    protected static ?int $navigationSort = 17;

    protected string $view = 'filament.pages.event-statistics';

    #[Url(as: 'mode', history: true)]
    public string $mode = 'single';

    #[Url(as: 'month', history: true)]
    public string $selectedMonth = '';

    public string $pendingMonth = '';

    #[Url(as: 'compare_a', history: true)]
    public string $comparisonMonthA = '';

    #[Url(as: 'compare_b', history: true)]
    public string $comparisonMonthB = '';

    public string $selectedMonthLabel = '';

    public string $comparisonLabelA = '';

    public string $comparisonLabelB = '';

    /** @var array{evenings_count: int, visits_count: int, unique_players_count: int, visits_per_player: float, revenue: float} */
    public array $monthlyStats = [];

    /** @var array<int, array{name: string, visits_count: int, percentage: float}> */
    public array $visitsByType = [];

    /** @var array<int, array{name: string, evenings_count: int, visits_count: int, unique_players_count: int, revenue: float}> */
    public array $typeStats = [];

    public array $comparisonStatsA = [];

    public array $comparisonStatsB = [];

    /** @var array<int, array<string, mixed>> */
    public array $comparisonTypeStats = [];

    public function mount(): void
    {
        if (! in_array($this->mode, ['single', 'comparison'], true)) {
            $this->mode = 'single';
        }

        if (! $this->isValidMonth($this->selectedMonth)) {
            $this->selectedMonth = now()->format('Y-m');
        }

        $this->pendingMonth = $this->selectedMonth;

        if (! $this->isValidMonth($this->comparisonMonthB)) {
            $this->comparisonMonthB = now()->startOfYear()->addMonth()->format('Y-m');
        }

        if (! $this->isValidMonth($this->comparisonMonthA)) {
            $this->comparisonMonthA = now()->startOfYear()->format('Y-m');
        }

        $this->refreshLabels();
        if ($this->mode === 'comparison') {
            $this->refreshComparisonStats();
        } else {
            $this->refreshMonthlyStats();
        }
    }

    public function setMode(string $mode): void
    {
        if (! in_array($mode, ['single', 'comparison'], true)) {
            return;
        }

        $this->mode = $mode;

        if ($mode === 'comparison' && $this->comparisonStatsA === []) {
            $this->refreshComparisonStats();
        } elseif ($mode === 'single' && $this->monthlyStats === []) {
            $this->refreshMonthlyStats();
        }
    }

    public function applyMonth(): void
    {
        $this->resetErrorBag('selectedMonth');

        if (! $this->isValidMonth($this->pendingMonth)) {
            $this->addError('selectedMonth', 'Выберите месяц.');

            return;
        }

        $this->selectedMonth = $this->pendingMonth;
        $this->selectedMonthLabel = $this->monthLabel($this->selectedMonth);
        $this->refreshMonthlyStats();
    }

    public function applyComparison(): void
    {
        $this->resetErrorBag('comparisonRange');

        if (! $this->isValidMonth($this->comparisonMonthA) || ! $this->isValidMonth($this->comparisonMonthB)) {
            $this->addError('comparisonRange', 'Выберите два месяца для сравнения.');

            return;
        }

        $this->comparisonLabelA = $this->monthLabel($this->comparisonMonthA);
        $this->comparisonLabelB = $this->monthLabel($this->comparisonMonthB);
        $this->refreshComparisonStats();
    }

    private function refreshLabels(): void
    {
        $this->selectedMonthLabel = $this->monthLabel($this->selectedMonth);
        $this->comparisonLabelA = $this->monthLabel($this->comparisonMonthA);
        $this->comparisonLabelB = $this->monthLabel($this->comparisonMonthB);
    }

    private function refreshMonthlyStats(): void
    {
        $data = $this->statisticsForMonth($this->selectedMonth);

        $this->monthlyStats = $data['summary'];
        $this->typeStats = $data['types'];
        $maximumVisits = max(1, (int) collect($this->typeStats)->max('visits_count'));
        $this->visitsByType = collect($this->typeStats)
            ->map(fn (array $row): array => [
                'name' => $row['name'],
                'visits_count' => $row['visits_count'],
                'percentage' => round(($row['visits_count'] / $maximumVisits) * 100, 2),
            ])
            ->all();
    }

    private function refreshComparisonStats(): void
    {
        $types = EveningType::query()->orderBy('name')->get(['id', 'name']);
        $dataA = $this->statisticsForMonth($this->comparisonMonthA, $types);
        $dataB = $this->statisticsForMonth($this->comparisonMonthB, $types);
        $this->comparisonStatsA = $dataA['summary'];
        $this->comparisonStatsB = $dataB['summary'];

        $typesA = collect($dataA['types'])->keyBy('id');
        $typesB = collect($dataB['types'])->keyBy('id');
        $maximumVisits = max(
            1,
            (int) $typesA->max('visits_count'),
            (int) $typesB->max('visits_count'),
        );

        $this->comparisonTypeStats = $typesA
            ->map(function (array $typeA, int $id) use ($typesB, $maximumVisits): array {
                $typeB = $typesB->get($id);

                return [
                    'id' => $id,
                    'name' => $typeA['name'],
                    'a' => $typeA,
                    'b' => $typeB,
                    'percentage_a' => round(($typeA['visits_count'] / $maximumVisits) * 100, 2),
                    'percentage_b' => round((($typeB['visits_count'] ?? 0) / $maximumVisits) * 100, 2),
                ];
            })
            ->sortByDesc(fn (array $row): int => max($row['a']['visits_count'], $row['b']['visits_count'] ?? 0))
            ->values()
            ->all();
    }

    /** @return array{summary: array<string, int|float>, types: array<int, array<string, int|float|string>>} */
    private function statisticsForMonth(string $month, ?Collection $types = null): array
    {
        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $end = $start->copy()->addMonth();

        $eveningsByType = Evening::query()
            ->where('played_at', '>=', $start)
            ->where('played_at', '<', $end)
            ->groupBy('evening_type_id')
            ->selectRaw('evening_type_id, COUNT(*) AS evenings_count')
            ->get();
        $eveningsCount = (int) $eveningsByType->sum('evenings_count');
        $eveningsByTypeId = $eveningsByType
            ->whereNotNull('evening_type_id')
            ->pluck('evenings_count', 'evening_type_id');

        $participantStats = EveningParticipant::query()
            ->whereHas('evening', fn ($query) => $query
                ->where('played_at', '>=', $start)
                ->where('played_at', '<', $end))
            ->selectRaw('COUNT(*) AS visits_count')
            ->selectRaw('COUNT(DISTINCT player_id) AS unique_players_count')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) AS revenue')
            ->first();

        $visitsCount = (int) ($participantStats?->visits_count ?? 0);
        $uniquePlayersCount = (int) ($participantStats?->unique_players_count ?? 0);

        $summary = [
            'evenings_count' => $eveningsCount,
            'visits_count' => $visitsCount,
            'unique_players_count' => $uniquePlayersCount,
            'visits_per_player' => $uniquePlayersCount === 0
                ? 0.0
                : round($visitsCount / $uniquePlayersCount, 2),
            'revenue' => (float) ($participantStats?->revenue ?? 0),
        ];

        $statsByTypeId = EveningParticipant::query()
            ->join('evenings', 'evenings.id', '=', 'evening_participants.evening_id')
            ->where('evenings.played_at', '>=', $start)
            ->where('evenings.played_at', '<', $end)
            ->whereNotNull('evenings.evening_type_id')
            ->groupBy('evenings.evening_type_id')
            ->selectRaw('evenings.evening_type_id')
            ->selectRaw('COUNT(*) AS visits_count')
            ->selectRaw('COUNT(DISTINCT evening_participants.player_id) AS unique_players_count')
            ->selectRaw('COALESCE(SUM(evening_participants.paid_amount), 0) AS revenue')
            ->get()
            ->keyBy('evening_type_id');

        $types ??= EveningType::query()->orderBy('name')->get(['id', 'name']);

        $typeRows = $types
            ->map(function (EveningType $type) use ($eveningsByTypeId, $statsByTypeId): array {
                $stats = $statsByTypeId->get($type->id);

                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'evenings_count' => (int) $eveningsByTypeId->get($type->id, 0),
                    'visits_count' => (int) ($stats?->visits_count ?? 0),
                    'unique_players_count' => (int) ($stats?->unique_players_count ?? 0),
                    'revenue' => (float) ($stats?->revenue ?? 0),
                ];
            })
            ->sortByDesc('visits_count')
            ->values();
        return [
            'summary' => $summary,
            'types' => $typeRows->all(),
        ];
    }

    private function monthLabel(string $month): string
    {
        return Str::ucfirst(Carbon::createFromFormat('!Y-m', $month)->locale('ru')->translatedFormat('F Y'));
    }

    private function isValidMonth(string $month): bool
    {
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) === 1;
    }
}
