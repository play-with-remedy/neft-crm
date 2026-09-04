<?php

namespace App\Filament\Pages;

use App\Support\PlayerCsvExporter;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class ExportPlayers extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $navigationLabel = 'Экспорт игроков';

    protected static ?string $title = 'Экспорт игроков';

    protected static UnitEnum|string|null $navigationGroup = 'Синхронизация';

    protected string $view = 'filament.pages.export-players';

    public function export(): StreamedResponse
    {
        return PlayerCsvExporter::downloadAll();
    }
}
