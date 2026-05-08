<?php

namespace App\Filament\Resources\Evenings\Pages;

use App\Filament\Resources\Evenings\EveningResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEvening extends CreateRecord
{
    protected static string $resource = EveningResource::class;
    protected static ?string $title = 'Новый вечер';
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
