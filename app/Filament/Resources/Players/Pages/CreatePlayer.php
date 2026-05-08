<?php

namespace App\Filament\Resources\Players\Pages;

use App\Filament\Resources\Players\PlayerResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePlayer extends CreateRecord
{
    protected static string $resource = PlayerResource::class;
    protected static ?string $title = 'Новый игрок';
     protected static ?string $breadcrumb = 'Создать';

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Создать'),

            $this->getCreateAnotherFormAction()
                ->label('Создать ещё'),

            $this->getCancelFormAction()
                ->label('Отмена'),
        ];
    }
}
