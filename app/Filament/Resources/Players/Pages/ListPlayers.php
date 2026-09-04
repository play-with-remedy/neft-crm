<?php

namespace App\Filament\Resources\Players\Pages;

use App\Filament\Resources\Players\PlayerResource;
use App\Support\PlayerCsvExporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlayers extends ListRecords
{
    protected static string $resource = PlayerResource::class;
    protected static ?string $title = 'Игроки';
    protected static ?string $breadcrumb = 'Список';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Экспорт CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => PlayerCsvExporter::downloadAll()),

            CreateAction::make()
                ->label('Новый игрок'),
        ];
    }
}
