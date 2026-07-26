<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Evenings\EveningResource;
use App\Filament\Resources\Players\PlayerResource;
use App\Models\Evening;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Collection;

class ClubStatsOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $current = $this->eveningsForMonth(now()->startOfMonth(), now()->endOfMonth());
        $previousDate = now()->subMonthNoOverflow();
        $previous = $this->eveningsForMonth(
            $previousDate->copy()->startOfMonth(),
            $previousDate->copy()->endOfMonth(),
        );

        $revenue = $this->revenue($current);
        $previousRevenue = $this->revenue($previous);
        $profit = $this->profit($current);
        $previousProfit = $this->profit($previous);
        $participants = (int) $current->sum('participants_count');
        $newPlayers = (int) $current->sum('new_players_count');

        return [
            Stat::make('Выручка', $this->money($revenue))
                ->description($this->comparison($revenue, $previousRevenue))
                ->descriptionIcon($this->comparisonIcon($revenue, $previousRevenue))
                ->descriptionColor($this->comparisonColor($revenue, $previousRevenue))
                ->icon('heroicon-o-banknotes')
                ->color('primary'),

            Stat::make('Прибыль', $this->money($profit))
                ->description($this->comparison($profit, $previousProfit))
                ->descriptionIcon($this->comparisonIcon($profit, $previousProfit))
                ->descriptionColor($this->comparisonColor($profit, $previousProfit))
                ->icon('heroicon-o-arrow-trending-up')
                ->color($profit < 0 ? 'danger' : 'success'),

            Stat::make('Вечеров', (string) $current->count())
                ->description('за текущий месяц')
                ->icon('heroicon-o-calendar-days')
                ->url(EveningResource::getUrl())
                ->color('info'),

            Stat::make('Посещений игроков', (string) $participants)
                ->description($newPlayers > 0 ? "Новых игроков: {$newPlayers}" : 'Новых игроков пока нет')
                ->descriptionIcon('heroicon-o-user-plus')
                ->icon('heroicon-o-user-group')
                ->url(PlayerResource::getUrl())
                ->color('warning'),
        ];
    }

    private function eveningsForMonth($from, $until): Collection
    {
        return Evening::query()
            ->whereBetween('played_at', [$from, $until])
            ->withSum('participants', 'paid_amount')
            ->withSum('staff', 'salary')
            ->withSum('expenses', 'amount')
            ->withCount('participants')
            ->withCount([
                'participants as new_players_count' => fn ($query) => $query->where('is_new_player', true),
            ])
            ->get();
    }

    private function revenue(Collection $evenings): int
    {
        return (int) $evenings->sum('participants_sum_paid_amount');
    }

    private function profit(Collection $evenings): int
    {
        return (int) $evenings->sum(
            fn (Evening $evening): int => (int) $evening->participants_sum_paid_amount
                - (int) $evening->staff_sum_salary
                - (int) $evening->expenses_sum_amount
                - (int) $evening->other_expenses
        );
    }

    private function money(int $amount): string
    {
        return number_format($amount, 0, ',', ' ') . ' BYN';
    }

    private function comparison(int $current, int $previous): string
    {
        if ($previous === 0) {
            return $current === 0 ? 'Нет данных за месяц' : 'Первые данные за период';
        }

        $change = (int) round((($current - $previous) / abs($previous)) * 100);

        return abs($change) . '% к прошлому месяцу';
    }

    private function comparisonColor(int $current, int $previous): string
    {
        return $current >= $previous ? 'success' : 'danger';
    }

    private function comparisonIcon(int $current, int $previous): string
    {
        return $current >= $previous
            ? 'heroicon-m-arrow-trending-up'
            : 'heroicon-m-arrow-trending-down';
    }
}
