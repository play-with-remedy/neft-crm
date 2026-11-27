<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ClubStatsOverview;
use App\Filament\Widgets\ClubWelcome;
use App\Filament\Widgets\MonthlyPerformanceChart;
use App\Filament\Widgets\TodaysBirthdays;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Обзор клуба';

    protected static ?string $navigationLabel = 'Главная';

    public function getWidgets(): array
    {
        return [
            ClubWelcome::class,
            ClubStatsOverview::class,
            MonthlyPerformanceChart::class,
            TodaysBirthdays::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'xl' => 2,
        ];
    }
}
