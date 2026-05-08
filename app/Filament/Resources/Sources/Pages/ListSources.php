<?php

namespace App\Filament\Resources\Sources\Pages;

use App\Filament\Resources\Sources\SourceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSources extends ListRecords
{
    protected static string $resource = SourceResource::class;

    protected static ?string $title = 'Источники';
    protected static ?string $breadcrumb = 'Список';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новый источник')
                ->modalHeading('Новый источник')
                ->modalSubmitActionLabel('Создать')
                ->modalCancelActionLabel('Отмена')
        ];
    }
}
