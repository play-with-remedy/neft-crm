<?php

namespace App\Filament\Resources\Certificates\Pages;

use App\Filament\Resources\Certificates\CertificateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCertificates extends ListRecords
{
    protected static string $resource = CertificateResource::class;

    protected static ?string $title = 'Сертификаты';

    protected static ?string $breadcrumb = 'Список';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новый сертификат')
                ->modalHeading('Новый сертификат')
                ->modalSubmitActionLabel('Создать')
                ->createAnother(false)
                ->modalCancelActionLabel('Отмена'),
        ];
    }
}
