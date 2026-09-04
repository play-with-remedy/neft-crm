<?php

namespace App\Filament\Pages;

use App\Support\EveningCsvExporter;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class ExportEvenings extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $navigationLabel = 'Экспорт вечеров';

    protected static ?string $title = 'Экспорт вечеров';

    protected static UnitEnum|string|null $navigationGroup = 'Синхронизация';

    protected string $view = 'filament.pages.export-evenings';

    public function export(): StreamedResponse
    {
        return EveningCsvExporter::downloadAll();
    }
}
