<?php

namespace App\Filament\Widgets;

use App\Models\Evening;
use Filament\Widgets\ChartWidget;

class MonthlyPerformanceChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'Динамика месяца';

    protected ?string $description = 'Выручка и все расходы по дням, BYN';

    protected ?string $maxHeight = '310px';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $evenings = Evening::query()
            ->whereBetween('played_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->withSum('participants', 'paid_amount')
            ->withSum('staff', 'salary')
            ->withSum('expenses', 'amount')
            ->orderBy('played_at')
            ->get()
            ->groupBy(fn (Evening $evening): string => $evening->played_at->format('Y-m-d'));

        return [
            'datasets' => [
                [
                    'label' => 'Выручка',
                    'data' => $evenings->map(
                        fn ($items): int => (int) $items->sum('participants_sum_paid_amount')
                    )->values()->all(),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Расходы',
                    'data' => $evenings->map(
                        fn ($items): int => (int) $items->sum(
                            fn (Evening $evening): int => (int) $evening->staff_sum_salary
                                + (int) $evening->expenses_sum_amount
                                + (int) $evening->other_expenses
                        )
                    )->values()->all(),
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.08)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $evenings->keys()
                ->map(fn (string $date): string => date('d.m', strtotime($date)))
                ->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
