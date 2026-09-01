<?php

namespace App\Filament\Pages;

use App\Models\EveningParticipant;
use App\Models\Player;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use UnitEnum;

class LtvAnalysis extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static ?string $slug = 'ltv-analysis';

    protected static ?string $navigationLabel = 'Анализ LTV';

    protected static ?string $title = 'Анализ LTV';

    protected static UnitEnum | string | null $navigationGroup = 'Отчеты';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.pages.ltv-analysis';

    #[Url(as: 'from', history: true)]
    public string $periodFrom = '';

    #[Url(as: 'until', history: true)]
    public string $periodUntil = '';

    public string $pendingPeriodFrom = '';

    public string $pendingPeriodUntil = '';

    #[Url(as: 'sort', history: true)]
    public string $sortColumn = 'month';

    #[Url(as: 'dir', history: true)]
    public string $sortDirection = 'desc';

    #[Url(as: 'page', history: true)]
    public int $tablePage = 1;

    #[Url(as: 'per_page', history: true)]
    public string $tablePerPage = '6';

    /** @var array{new_players_count: int, revenue: float, average_ltv: float, average_visits: float, average_lifetime_days: float} */
    public array $summary = [];

    /** @var array<int, array{month: string, label: string, new_players_count: int, revenue: float, average_ltv: float, average_visits: float, average_lifetime_days: float}> */
    public array $rows = [];

    public function mount(): void
    {
        if (! in_array($this->tablePerPage, ['3', '6', '12', 'all'], true)) {
            $this->tablePerPage = '6';
        }

        $this->tablePage = max(1, $this->tablePage);

        if (! $this->isValidRange($this->periodFrom, $this->periodUntil)) {
            $this->periodFrom = now()->startOfYear()->format('Y-m');
            $this->periodUntil = now()->format('Y-m');
        }

        $this->pendingPeriodFrom = $this->periodFrom;
        $this->pendingPeriodUntil = $this->periodUntil;
        $this->refreshStatistics();
    }

    public function applyPeriodRange(): void
    {
        $this->resetErrorBag('periodRange');

        if (! $this->isValidRange($this->pendingPeriodFrom, $this->pendingPeriodUntil)) {
            $this->addError('periodRange', 'Начальный месяц не может быть позже конечного.');

            return;
        }

        $this->periodFrom = $this->pendingPeriodFrom;
        $this->periodUntil = $this->pendingPeriodUntil;
        $this->tablePage = 1;
        $this->refreshStatistics();
    }

    public function sortTable(string $column): void
    {
        if (! in_array($column, $this->sortableColumns(), true)) {
            return;
        }

        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'desc';
        }

        $this->tablePage = 1;
        $this->sortRows();
    }

    public function updatedTablePerPage(): void
    {
        if (! in_array($this->tablePerPage, ['3', '6', '12', 'all'], true)) {
            $this->tablePerPage = '6';
        }

        $this->tablePage = 1;
    }

    public function previousTablePage(): void
    {
        $this->tablePage = max(1, $this->tablePage - 1);
    }

    public function nextTablePage(): void
    {
        $this->tablePage = min($this->getTablePages(), $this->tablePage + 1);
    }

    /** @return array<int, array<string, int|float|string>> */
    public function getVisibleRows(): array
    {
        if ($this->tablePerPage === 'all') {
            return $this->rows;
        }

        $perPage = (int) $this->tablePerPage;

        return array_slice($this->rows, ($this->tablePage - 1) * $perPage, $perPage);
    }

    public function getTablePages(): int
    {
        if ($this->tablePerPage === 'all') {
            return 1;
        }

        return max(1, (int) ceil(count($this->rows) / (int) $this->tablePerPage));
    }

    private function refreshStatistics(): void
    {
        $from = Carbon::createFromFormat('!Y-m', $this->periodFrom)->startOfMonth();
        $until = Carbon::createFromFormat('!Y-m', $this->periodUntil)->startOfMonth()->addMonth();

        $participationStats = EveningParticipant::query()
            ->join('evenings', 'evenings.id', '=', 'evening_participants.evening_id')
            ->groupBy('evening_participants.player_id')
            ->selectRaw('evening_participants.player_id')
            ->selectRaw('COUNT(*) AS participations_count')
            ->selectRaw('COALESCE(SUM(evening_participants.paid_amount), 0) AS ltv_total')
            ->selectRaw('MAX(evenings.played_at) AS last_visit_at');

        $players = Player::query()
            ->leftJoinSub($participationStats, 'participation_stats', function ($join): void {
                $join->on('participation_stats.player_id', '=', 'players.id');
            })
            ->where('first_visit_at', '>=', $from->toDateString())
            ->where('first_visit_at', '<', $until->toDateString())
            ->select(['players.id', 'players.first_visit_at'])
            ->selectRaw('COALESCE(participation_stats.participations_count, 0) AS participations_count')
            ->selectRaw('COALESCE(participation_stats.ltv_total, 0) AS ltv_total')
            ->addSelect('participation_stats.last_visit_at')
            ->get();

        $this->summary = $this->summarizePlayers($players);
        $playersByMonth = $players->groupBy(
            fn (Player $player): string => $player->first_visit_at->format('Y-m'),
        );
        $rows = [];

        for ($month = $from->copy(); $month->lt($until); $month->addMonth()) {
            $key = $month->format('Y-m');
            $stats = $this->summarizePlayers($playersByMonth->get($key, collect()));
            $rows[] = [
                'month' => $key,
                'label' => Str::ucfirst($month->copy()->locale('ru')->translatedFormat('F Y')),
                ...$stats,
            ];
        }

        $this->rows = $rows;
        $this->sortRows();
        $this->tablePage = min(max(1, $this->tablePage), $this->getTablePages());
    }

    /** @return array{new_players_count: int, revenue: float, average_ltv: float, average_visits: float, average_lifetime_days: float} */
    private function summarizePlayers(Collection $players): array
    {
        $playersCount = $players->count();
        $revenue = (float) $players->sum(fn (Player $player): float => (float) ($player->ltv_total ?? 0));
        $visits = (int) $players->sum(fn (Player $player): int => (int) $player->participations_count);
        $lifetimeDays = (float) $players->sum(function (Player $player): int {
            if (! $player->first_visit_at || ! $player->last_visit_at) {
                return 0;
            }

            return (int) $player->first_visit_at
                ->copy()
                ->startOfDay()
                ->diffInDays(Carbon::parse($player->last_visit_at)->startOfDay());
        });

        return [
            'new_players_count' => $playersCount,
            'revenue' => $revenue,
            'average_ltv' => $playersCount === 0 ? 0.0 : round($revenue / $playersCount, 2),
            'average_visits' => $playersCount === 0 ? 0.0 : round($visits / $playersCount, 1),
            'average_lifetime_days' => $playersCount === 0 ? 0.0 : round($lifetimeDays / $playersCount, 1),
        ];
    }

    private function isValidRange(string $from, string $until): bool
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $from) !== 1
            || preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $until) !== 1) {
            return false;
        }

        return Carbon::createFromFormat('!Y-m', $from)
            ->lte(Carbon::createFromFormat('!Y-m', $until));
    }

    private function sortRows(): void
    {
        if (! in_array($this->sortColumn, $this->sortableColumns(), true)) {
            $this->sortColumn = 'month';
        }

        if (! in_array($this->sortDirection, ['asc', 'desc'], true)) {
            $this->sortDirection = 'desc';
        }

        usort($this->rows, function (array $left, array $right): int {
            $comparison = $left[$this->sortColumn] <=> $right[$this->sortColumn];

            if ($comparison === 0) {
                $comparison = $left['month'] <=> $right['month'];
            }

            return $this->sortDirection === 'asc' ? $comparison : -$comparison;
        });
    }

    /** @return array<int, string> */
    private function sortableColumns(): array
    {
        return [
            'month',
            'new_players_count',
            'revenue',
            'average_ltv',
            'average_visits',
            'average_lifetime_days',
        ];
    }
}
