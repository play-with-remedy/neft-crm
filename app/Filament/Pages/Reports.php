<?php

namespace App\Filament\Pages;

use App\Services\ReportService;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;
use BackedEnum;

class Reports extends Page
{
    protected string $view = 'filament.pages.reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;
    protected static UnitEnum|string|null $navigationGroup = 'Отчеты';

    protected static ?string $navigationLabel = 'Отчёт Тестовый';
    protected static ?string $title = 'Отчёт Тестовый';

    public $monthlyRevenue;
    public $playerMonthlyRevenue;

    public function mount(ReportService $reportService): void
    {
        $this->monthlyRevenue = $reportService->getMonthlyRevenue();
        $this->playerMonthlyRevenue = $reportService->getPlayerMonthlyRevenue();
    }
}