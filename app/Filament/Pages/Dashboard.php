<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ClubStatsOverview;
use App\Filament\Widgets\ClubWelcome;
use App\Filament\Widgets\LatestEvenings;
use App\Filament\Widgets\MonthlyPerformanceChart;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Обзор клуба';

    protected static ?string $navigationLabel = 'Главная';

    public function getSubheading(): string | Htmlable | null
    {
        return now()
            ->locale('ru')
            ->translatedFormat('F Y') . ' · ключевые показатели и последние события';
    }

    public function getWidgets(): array
    {
        return [
            ClubWelcome::class,
            ClubStatsOverview::class,
            MonthlyPerformanceChart::class,
            LatestEvenings::class,
        ];
    }

    public function getColumns(): int | array
    {
        return [
            'default' => 1,
            'xl' => 2,
        ];
    }
}
