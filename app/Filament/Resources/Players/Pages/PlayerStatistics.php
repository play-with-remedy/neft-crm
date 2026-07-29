<?php

namespace App\Filament\Resources\Players\Pages;

use App\Filament\Resources\Players\PlayerResource;
use App\Filament\Widgets\PlayerActivityChart;
use App\Filament\Widgets\PlayerStatsOverview;
use App\Filament\Widgets\PlayerVisitHistory;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class PlayerStatistics extends ViewRecord
{
    protected static string $resource = PlayerResource::class;

    protected static ?string $breadcrumb = 'Статистика';

    public function getTitle(): string
    {
        return 'Статистика: ' . $this->record->nickname;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('details')
                ->label('Карточка игрока')
                ->icon('heroicon-o-user')
                ->color('gray')
                ->url(fn (): string => PlayerResource::getUrl('view', ['record' => $this->record])),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PlayerStatsOverview::class,
            PlayerActivityChart::class,
            PlayerVisitHistory::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([]);
    }
}
