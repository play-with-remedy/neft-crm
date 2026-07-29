<?php

namespace App\Filament\Widgets;

use App\Models\Player;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class PlayerActivityChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Посещения и оплаты по месяцам';

    protected ?string $description = 'Последние 12 месяцев';

    protected ?string $maxHeight = '340px';

    protected ?string $pollingInterval = null;

    public ?Player $record = null;

    protected function getData(): array
    {
        $start = now()->subMonths(11)->startOfMonth();
        $months = collect(range(0, 11))
            ->map(fn (int $offset): Carbon => $start->copy()->addMonths($offset));

        $participations = $this->record
            ?->participations()
            ->whereHas('evening', fn ($query) => $query->where('played_at', '>=', $start))
            ->with('evening:id,played_at')
            ->get()
            ->groupBy(fn ($participation): string => $participation->evening->played_at->format('Y-m'));

        return [
            'datasets' => [
                [
                    'type' => 'bar',
                    'label' => 'Оплачено, BYN',
                    'data' => $months
                        ->map(fn (Carbon $month): int => (int) ($participations?->get($month->format('Y-m'))?->sum('paid_amount') ?? 0))
                        ->all(),
                    'backgroundColor' => 'rgba(245, 158, 11, 0.72)',
                    'borderColor' => '#f59e0b',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                    'yAxisID' => 'y',
                ],
                [
                    'type' => 'line',
                    'label' => 'Посещений',
                    'data' => $months
                        ->map(fn (Carbon $month): int => $participations?->get($month->format('Y-m'))?->count() ?? 0)
                        ->all(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => '#10b981',
                    'pointBackgroundColor' => '#10b981',
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                    'tension' => 0.3,
                    'yAxisID' => 'visits',
                ],
            ],
            'labels' => $months
                ->map(fn (Carbon $month): string => $month->locale('ru')->translatedFormat('M Y'))
                ->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'BYN',
                    ],
                ],
                'visits' => [
                    'beginAtZero' => true,
                    'position' => 'right',
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                    'ticks' => [
                        'precision' => 0,
                        'stepSize' => 1,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Посещения',
                    ],
                ],
            ],
        ];
    }
}
