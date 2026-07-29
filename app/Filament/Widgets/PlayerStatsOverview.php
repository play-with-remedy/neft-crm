<?php

namespace App\Filament\Widgets;

use App\Models\Player;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlayerStatsOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    public ?Player $record = null;

    protected function getStats(): array
    {
        $summary = $this->record
            ?->participations()
            ->join('evenings', 'evenings.id', '=', 'evening_participants.evening_id')
            ->selectRaw('COUNT(*) as visits_count')
            ->selectRaw('COALESCE(SUM(evening_participants.paid_amount), 0) as paid_total')
            ->selectRaw('MIN(evenings.played_at) as first_visit')
            ->selectRaw('MAX(evenings.played_at) as last_visit')
            ->first();

        return [
            Stat::make('Посещений', number_format((int) ($summary?->visits_count ?? 0), 0, ',', ' '))
                ->description('за всё время')
                ->icon('heroicon-o-calendar-days')
                ->color('info'),

            Stat::make('Всего оплачено', $this->money((int) ($summary?->paid_total ?? 0)))
                ->description('за все посещения')
                ->icon('heroicon-o-banknotes')
                ->color('primary'),

            Stat::make('Первый визит', $this->date($summary?->first_visit ?? $this->record?->first_visit_at))
                ->description('начало истории игрока')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('gray'),

            Stat::make('Последний визит', $this->date($summary?->last_visit))
                ->description('последнее посещение')
                ->icon('heroicon-o-clock')
                ->color('success'),
        ];
    }

    private function money(int $amount): string
    {
        return number_format($amount, 0, ',', ' ') . ' BYN';
    }

    private function date($date): string
    {
        return filled($date) ? date('d.m.Y', strtotime((string) $date)) : '—';
    }
}
