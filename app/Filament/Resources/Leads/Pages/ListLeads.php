<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected static ?string $title = 'Лиды';

    protected static ?string $breadcrumb = 'Список';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новый лид')
                ->modalHeading('Новый лид')
                ->modalSubmitActionLabel('Создать')
                ->createAnother(false)
                ->modalCancelActionLabel('Отмена'),
        ];
    }
}
