<?php

namespace App\Filament\Resources\Players\Pages;

use App\Filament\Resources\Players\PlayerResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlayer extends ViewRecord
{
    protected static string $resource = PlayerResource::class;
    protected static ?string $breadcrumb = 'Детали';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('statistics')
                ->label('Статистика')
                ->icon('heroicon-o-chart-bar')
                ->color('primary')
                ->url(fn (): string => PlayerResource::getUrl('statistics', ['record' => $this->record])),

            Action::make('autumn_case')
                ->label('Осеннее дело')
                ->icon('/images/autumn-leaf.svg?v=3')
                ->color('warning')
                ->url(fn (): string => PlayerResource::getUrl('autumn-case', ['record' => $this->record])),

            EditAction::make()
                ->label('Редактировать'),
        ];
    }

    public function getTitle(): string
    {
        return $this->record->nickname;
    }
}
