<?php

namespace App\Filament\Resources\Evenings\Pages;

use App\Filament\Pages\ImportEvenings;
use App\Filament\Resources\EveningLockPeriods\EveningLockPeriodResource;
use App\Filament\Resources\Evenings\EveningResource;
use App\Support\EveningCsvExporter;
use App\Support\FilamentAccess;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEvenings extends ListRecords
{
    protected static string $resource = EveningResource::class;

    protected static ?string $title = 'Вечера';

    protected static ?string $breadcrumb = 'Список';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Импорт CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->authorize(fn (): bool => FilamentAccess::isSuperAdmin())
                ->url(ImportEvenings::getUrl()),

            Action::make('export')
                ->label('Экспорт CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->authorize(fn (): bool => FilamentAccess::isSuperAdmin())
                ->action(fn () => EveningCsvExporter::downloadAll()),

            Action::make('lock_periods')
                ->label('Закрытые периоды')
                ->icon('heroicon-o-lock-closed')
                ->color('gray')
                ->authorize(fn (): bool => FilamentAccess::isSuperAdmin())
                ->url(EveningLockPeriodResource::getUrl()),

            CreateAction::make()
                ->label('Новый вечер'),
        ];
    }
}
