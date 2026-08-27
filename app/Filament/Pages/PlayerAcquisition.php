<?php

namespace App\Filament\Pages;

use App\Models\Source;
use App\Models\Player;
use App\Models\EveningParticipant;
use App\Models\SourceAdvertisingExpense;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use UnitEnum;

class PlayerAcquisition extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $slug = 'player-acquisition';

    protected static ?string $navigationLabel = 'Привлечение игроков';

    protected static ?string $title = 'Привлечение игроков';

    protected static UnitEnum | string | null $navigationGroup = 'Отчеты';

    protected static ?int $navigationSort = 25;

    protected string $view = 'filament.pages.player-acquisition';

    #[Url(as: 'from', history: true)]
    public string $periodFrom = '';

    #[Url(as: 'until', history: true)]
    public string $periodUntil = '';

    public string $pendingPeriodFrom = '';

    public string $pendingPeriodUntil = '';

    /** @var array<int, array{id: int, name: string, new_players_count: int, new_players_percentage: float, advertising_expenses: float, paid_channels_cac: float|null, average_ltv: float, regular_conversion: float, paid_total: float, regular_players_count: int}> */
    public array $sources = [];

    public string $editingSourceName = '';

    public ?int $editingSourceId = null;

    /** @var array<string, string> */
    public array $advertisingExpenseMonths = [];

    /** @var array<string, int|float|string|null> */
    public array $advertisingExpenses = [];

    /** @var array<int, array{month: string, label: string, new_players_count: int, advertising_expenses: float, general_cac: float|null, paid_channels_cac: float|null, average_ltv: float, average_check: float, average_frequency: float, regular_conversion: float, visits_count: int, paid_total: float, regular_players_count: int, observation_player_months: int, paid_channels_new_players_count: int}> */
    public array $monthlyDynamics = [];

    /** @return array{new_players_count: int, advertising_expenses: float, general_cac: float|null, paid_channels_cac: float|null, average_ltv: float, average_check: float, average_frequency: float, regular_conversion: float} */
    public function getMonthlyDynamicsSummary(): array
    {
        $rows = collect($this->monthlyDynamics);
        $newPlayersCount = (int) $rows->sum('new_players_count');
        $advertisingExpenses = (float) $rows->sum('advertising_expenses');
        $visitsCount = (int) $rows->sum('visits_count');
        $paidTotal = (float) $rows->sum('paid_total');
        $regularPlayersCount = (int) $rows->sum('regular_players_count');
        $observationPlayerMonths = (int) $rows->sum('observation_player_months');
        $paidChannelsNewPlayersCount = (int) $rows->sum('paid_channels_new_players_count');

        return [
            'new_players_count' => $newPlayersCount,
            'advertising_expenses' => $advertisingExpenses,
            'general_cac' => $advertisingExpenses <= 0 || $newPlayersCount === 0
                ? null
                : round($advertisingExpenses / $newPlayersCount, 2),
            'paid_channels_cac' => $advertisingExpenses <= 0 || $paidChannelsNewPlayersCount === 0
                ? null
                : round($advertisingExpenses / $paidChannelsNewPlayersCount, 2),
            'average_ltv' => $newPlayersCount === 0 ? 0 : round($paidTotal / $newPlayersCount, 2),
            'average_check' => $visitsCount === 0 ? 0 : round($paidTotal / $visitsCount, 2),
            'average_frequency' => $observationPlayerMonths === 0
                ? 0
                : round($visitsCount / $observationPlayerMonths, 1),
            'regular_conversion' => $newPlayersCount === 0
                ? 0
                : round(($regularPlayersCount / $newPlayersCount) * 100, 1),
        ];
    }

    /** @return array{new_players_count: int, new_players_percentage: float, advertising_expenses: float, general_cac: float|null, paid_channels_cac: float|null, average_ltv: float, regular_conversion: float} */
    public function getSourcesSummary(): array
    {
        $rows = collect($this->sources);
        $newPlayersCount = (int) $rows->sum('new_players_count');
        $advertisingExpenses = (float) $rows->sum('advertising_expenses');
        $paidTotal = (float) $rows->sum('paid_total');
        $regularPlayersCount = (int) $rows->sum('regular_players_count');
        $paidChannelsNewPlayersCount = (int) $rows
            ->where('advertising_expenses', '>', 0)
            ->sum('new_players_count');

        return [
            'new_players_count' => $newPlayersCount,
            'new_players_percentage' => $newPlayersCount === 0 ? 0 : 100,
            'advertising_expenses' => $advertisingExpenses,
            'general_cac' => $advertisingExpenses <= 0 || $newPlayersCount === 0
                ? null
                : round($advertisingExpenses / $newPlayersCount, 2),
            'paid_channels_cac' => $advertisingExpenses <= 0 || $paidChannelsNewPlayersCount === 0
                ? null
                : round($advertisingExpenses / $paidChannelsNewPlayersCount, 2),
            'average_ltv' => $newPlayersCount === 0 ? 0 : round($paidTotal / $newPlayersCount, 2),
            'regular_conversion' => $newPlayersCount === 0
                ? 0
                : round(($regularPlayersCount / $newPlayersCount) * 100, 1),
        ];
    }

    public function mount(): void
    {
        if (! $this->isValidRange($this->periodFrom, $this->periodUntil)) {
            $this->periodFrom = now()->format('Y-m');
            $this->periodUntil = $this->periodFrom;
        }

        $this->pendingPeriodFrom = $this->periodFrom;
        $this->pendingPeriodUntil = $this->periodUntil;

        $this->refreshSources();
        $this->refreshMonthlyDynamics();
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

        $this->periodFrom = $this->pendingPeriodFrom;
        $this->periodUntil = $this->pendingPeriodUntil;
        $this->refreshSources();
    }

    public function openAdvertisingExpenses(int $sourceId): void
    {
        $source = Source::query()->findOrFail($sourceId);
        $months = $this->periodMonths();
        $savedExpenses = SourceAdvertisingExpense::query()
            ->where('source_id', $source->id)
            ->whereIn('month', array_keys($months))
            ->pluck('amount', 'month');

        $this->editingSourceName = $source->name;
        $this->editingSourceId = $source->id;
        $this->advertisingExpenseMonths = $months;
        $this->advertisingExpenses = collect($months)
            ->mapWithKeys(fn (string $label, string $month): array => [
                $month => $savedExpenses->get($month, 0),
            ])
            ->all();
        $this->resetValidation();

        $this->dispatch('open-modal', id: 'source-advertising-expenses');
    }

    public function saveAdvertisingExpenses(): void
    {
        $source = Source::query()->findOrFail($this->editingSourceId);

        $this->validate([
            'advertisingExpenses.*' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ], [
            'advertisingExpenses.*.numeric' => 'Введите корректную сумму.',
            'advertisingExpenses.*.min' => 'Сумма не может быть отрицательной.',
            'advertisingExpenses.*.max' => 'Сумма слишком велика.',
        ]);

        $now = now();
        $rows = collect($this->advertisingExpenseMonths)
            ->map(fn (string $label, string $month): array => [
                'source_id' => $source->id,
                'month' => $month,
                'amount' => (float) ($this->advertisingExpenses[$month] ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        SourceAdvertisingExpense::query()->upsert(
            $rows,
            ['source_id', 'month'],
            ['amount', 'updated_at'],
        );

        $this->refreshSources();
        $this->refreshMonthlyDynamics();
        $this->dispatch('close-modal', id: 'source-advertising-expenses');
    }

    private function isValidRange(string $from, string $until): bool
    {
        if (! $this->isValidPeriod($from) || ! $this->isValidPeriod($until)) {
            return false;
        }

        $start = Carbon::createFromFormat('!Y-m', $from)->startOfMonth();
        $end = Carbon::createFromFormat('!Y-m', $until)->startOfMonth();

        return $start->lessThanOrEqualTo($end) && $start->diffInMonths($end) < 12;
    }

    private function isValidPeriod(string $period): bool
    {
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period) === 1;
    }

    private function refreshSources(): void
    {
        $start = Carbon::createFromFormat('!Y-m', $this->periodFrom)->startOfMonth();
        $end = Carbon::createFromFormat('!Y-m', $this->periodUntil)->startOfMonth()->addMonth();

        $totalNewPlayers = Player::query()
            ->where('first_visit_at', '>=', $start->toDateString())
            ->where('first_visit_at', '<', $end->toDateString())
            ->count();

        $paymentsBySource = EveningParticipant::query()
            ->join('players', 'players.id', '=', 'evening_participants.player_id')
            ->where('players.first_visit_at', '>=', $start->toDateString())
            ->where('players.first_visit_at', '<', $end->toDateString())
            ->groupByRaw('COALESCE(players.source_id, 0)')
            ->selectRaw('COALESCE(players.source_id, 0) AS source_key, SUM(evening_participants.paid_amount) AS paid_total')
            ->pluck('paid_total', 'source_key');

        $regularPlayersBySource = Player::query()
            ->where('first_visit_at', '>=', $start->toDateString())
            ->where('first_visit_at', '<', $end->toDateString())
            ->whereHas('participations', operator: '>=', count: 21)
            ->groupByRaw('COALESCE(source_id, 0)')
            ->selectRaw('COALESCE(source_id, 0) AS source_key, COUNT(*) AS regular_players_count')
            ->pluck('regular_players_count', 'source_key');

        $advertisingExpensesBySource = SourceAdvertisingExpense::query()
            ->where('month', '>=', $start->toDateString())
            ->where('month', '<', $end->toDateString())
            ->groupBy('source_id')
            ->selectRaw('source_id, SUM(amount) AS amount_total')
            ->pluck('amount_total', 'source_id');

        $sourceRows = Source::query()
            ->withCount([
                'players as new_players_count' => fn ($query) => $query
                    ->where('first_visit_at', '>=', $start->toDateString())
                    ->where('first_visit_at', '<', $end->toDateString()),
            ])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (Source $source) use ($advertisingExpensesBySource, $paymentsBySource, $regularPlayersBySource, $totalNewPlayers): array {
                $newPlayersCount = (int) $source->new_players_count;
                $paidTotal = (float) $paymentsBySource->get($source->id, 0);
                $regularPlayersCount = (int) $regularPlayersBySource->get($source->id, 0);
                $advertisingExpenses = (float) $advertisingExpensesBySource->get($source->id, 0);

                return [
                    'id' => $source->id,
                    'name' => $source->name,
                    'new_players_count' => $newPlayersCount,
                    'new_players_percentage' => $totalNewPlayers === 0
                        ? 0
                        : round(($newPlayersCount / $totalNewPlayers) * 100, 1),
                    'advertising_expenses' => $advertisingExpenses,
                    'paid_channels_cac' => $advertisingExpenses <= 0 || $newPlayersCount === 0
                        ? null
                        : round($advertisingExpenses / $newPlayersCount, 2),
                    'average_ltv' => $newPlayersCount === 0
                        ? 0
                        : round($paidTotal / $newPlayersCount, 2),
                    'regular_conversion' => $newPlayersCount === 0
                        ? 0
                        : round(($regularPlayersCount / $newPlayersCount) * 100, 1),
                    'paid_total' => $paidTotal,
                    'regular_players_count' => $regularPlayersCount,
                ];
            })
            ->values();

        $playersWithoutSource = max(0, $totalNewPlayers - (int) $sourceRows->sum('new_players_count'));
        $paidWithoutSource = (float) $paymentsBySource->get(0, 0);
        $regularPlayersWithoutSource = (int) $regularPlayersBySource->get(0, 0);

        $sourceRows->push([
            'id' => 0,
            'name' => 'Источник не указан',
            'new_players_count' => $playersWithoutSource,
            'new_players_percentage' => $totalNewPlayers === 0
                ? 0
                : round(($playersWithoutSource / $totalNewPlayers) * 100, 1),
            'advertising_expenses' => 0,
            'paid_channels_cac' => null,
            'average_ltv' => $playersWithoutSource === 0
                ? 0
                : round($paidWithoutSource / $playersWithoutSource, 2),
            'regular_conversion' => $playersWithoutSource === 0
                ? 0
                : round(($regularPlayersWithoutSource / $playersWithoutSource) * 100, 1),
            'paid_total' => $paidWithoutSource,
            'regular_players_count' => $regularPlayersWithoutSource,
        ]);

        $this->sources = $sourceRows
            ->sortByDesc('new_players_count')
            ->values()
            ->all();
    }

    private function refreshMonthlyDynamics(): void
    {
        $participationStats = EveningParticipant::query()
            ->selectRaw('player_id, COUNT(*) AS visits_count, SUM(paid_amount) AS paid_total')
            ->groupBy('player_id');

        $rows = Player::query()
            ->leftJoinSub($participationStats, 'participation_stats', function ($join): void {
                $join->on('participation_stats.player_id', '=', 'players.id');
            })
            ->whereNotNull('players.first_visit_at')
            ->selectRaw('EXTRACT(YEAR FROM players.first_visit_at) AS visit_year')
            ->selectRaw('EXTRACT(MONTH FROM players.first_visit_at) AS visit_month')
            ->selectRaw('COUNT(players.id) AS new_players_count')
            ->selectRaw('COALESCE(SUM(participation_stats.visits_count), 0) AS visits_count')
            ->selectRaw('COALESCE(SUM(participation_stats.paid_total), 0) AS paid_total')
            ->selectRaw('SUM(CASE WHEN COALESCE(participation_stats.visits_count, 0) >= 21 THEN 1 ELSE 0 END) AS regular_players_count')
            ->groupByRaw('EXTRACT(YEAR FROM players.first_visit_at), EXTRACT(MONTH FROM players.first_visit_at)')
            ->orderByDesc('visit_year')
            ->orderByDesc('visit_month')
            ->get();

        $expensesByMonth = SourceAdvertisingExpense::query()
            ->groupBy('month')
            ->selectRaw('month, SUM(amount) AS amount_total')
            ->pluck('amount_total', 'month');

        $paidChannelsNewPlayersByMonth = Player::query()
            ->join('source_advertising_expenses as advertising_expenses', function ($join): void {
                $join
                    ->on('advertising_expenses.source_id', '=', 'players.source_id')
                    ->whereRaw("advertising_expenses.month = DATE_TRUNC('month', players.first_visit_at)::date")
                    ->where('advertising_expenses.amount', '>', 0);
            })
            ->whereNotNull('players.first_visit_at')
            ->selectRaw('EXTRACT(YEAR FROM players.first_visit_at) AS visit_year')
            ->selectRaw('EXTRACT(MONTH FROM players.first_visit_at) AS visit_month')
            ->selectRaw('COUNT(players.id) AS players_count')
            ->groupByRaw('EXTRACT(YEAR FROM players.first_visit_at), EXTRACT(MONTH FROM players.first_visit_at)')
            ->get()
            ->mapWithKeys(fn (Player $row): array => [
                sprintf('%04d-%02d', (int) $row->visit_year, (int) $row->visit_month) => (int) $row->players_count,
            ]);

        $this->monthlyDynamics = $rows
            ->map(function (Player $row) use ($expensesByMonth, $paidChannelsNewPlayersByMonth): array {
                $month = sprintf('%04d-%02d', (int) $row->visit_year, (int) $row->visit_month);
                $monthDate = "{$month}-01";
                $newPlayersCount = (int) $row->new_players_count;
                $visitsCount = (int) $row->visits_count;
                $paidTotal = (float) $row->paid_total;
                $regularPlayersCount = (int) $row->regular_players_count;
                $advertisingExpenses = (float) $expensesByMonth->get($monthDate, 0);
                $paidChannelsNewPlayersCount = (int) $paidChannelsNewPlayersByMonth->get($month, 0);
                $observationMonths = max(
                    1,
                    (int) Carbon::createFromFormat('!Y-m', $month)
                        ->startOfMonth()
                        ->diffInMonths(now()->startOfMonth()) + 1,
                );

                return [
                    'month' => $month,
                    'label' => Str::ucfirst(
                        Carbon::createFromFormat('!Y-m', $month)
                            ->locale('ru')
                            ->translatedFormat('F Y')
                    ),
                    'new_players_count' => $newPlayersCount,
                    'advertising_expenses' => $advertisingExpenses,
                    'general_cac' => $advertisingExpenses <= 0 || $newPlayersCount === 0
                        ? null
                        : round($advertisingExpenses / $newPlayersCount, 2),
                    'paid_channels_cac' => $advertisingExpenses <= 0 || $paidChannelsNewPlayersCount === 0
                        ? null
                        : round($advertisingExpenses / $paidChannelsNewPlayersCount, 2),
                    'average_ltv' => $newPlayersCount === 0 ? 0 : round($paidTotal / $newPlayersCount, 2),
                    'average_check' => $visitsCount === 0 ? 0 : round($paidTotal / $visitsCount, 2),
                    'average_frequency' => $newPlayersCount === 0
                        ? 0
                        : round($visitsCount / ($newPlayersCount * $observationMonths), 1),
                    'regular_conversion' => $newPlayersCount === 0
                        ? 0
                        : round(($regularPlayersCount / $newPlayersCount) * 100, 1),
                    'visits_count' => $visitsCount,
                    'paid_total' => $paidTotal,
                    'regular_players_count' => $regularPlayersCount,
                    'observation_player_months' => $newPlayersCount * $observationMonths,
                    'paid_channels_new_players_count' => $paidChannelsNewPlayersCount,
                ];
            })
            ->all();
    }

    /** @return array<string, string> */
    private function periodMonths(): array
    {
        $start = Carbon::createFromFormat('!Y-m', $this->periodFrom)->startOfMonth();
        $end = Carbon::createFromFormat('!Y-m', $this->periodUntil)->startOfMonth();
        $months = [];

        for ($month = $start->copy(); $month->lessThanOrEqualTo($end); $month->addMonth()) {
            $months[$month->toDateString()] = Str::ucfirst($month->locale('ru')->translatedFormat('F Y'));
        }

        return $months;
    }
}
