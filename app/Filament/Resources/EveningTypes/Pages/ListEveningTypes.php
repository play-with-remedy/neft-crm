<?php

namespace App\Filament\Resources\EveningTypes\Pages;

use App\Filament\Resources\EveningTypes\EveningTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEveningTypes extends ListRecords
{
    protected static string $resource = EveningTypeResource::class;

    protected static ?string $title = 'Типы проектов';
    protected static ?string $breadcrumb = 'Список';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новый тип проекта')
                ->modalHeading('Новый тип проекта')
                ->modalSubmitActionLabel('Создать')
                ->createAnother(false)
                ->modalCancelActionLabel('Отмена')
        ];
    }
}
