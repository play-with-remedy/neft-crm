<?php

namespace App\Filament\Resources\Hosts\Pages;

use App\Filament\Resources\Hosts\HostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHosts extends ListRecords
{
    protected static string $resource = HostResource::class;

    protected static ?string $title = 'Админы';
    protected static ?string $breadcrumb = 'Список';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новый админ')
                ->modalHeading('Новый админ')
                ->modalSubmitActionLabel('Создать')
                ->modalCancelActionLabel('Отмена')
        ];
    }
}
