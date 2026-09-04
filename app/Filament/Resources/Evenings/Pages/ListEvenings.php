<?php

namespace App\Filament\Resources\Evenings\Pages;

use App\Filament\Resources\Evenings\EveningResource;
use App\Support\EveningCsvExporter;
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
            Action::make('export')
                ->label('Экспорт CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => EveningCsvExporter::downloadAll()),

            CreateAction::make()
                ->label('Новый вечер'),
        ];
    }
}
