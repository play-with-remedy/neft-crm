<?php

namespace App\Filament\Resources\PaymentTypes\Pages;

use App\Filament\Resources\PaymentTypes\PaymentTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentTypes extends ListRecords
{
    protected static string $resource = PaymentTypeResource::class;

    protected static ?string $title = 'Типы оплаты';
    protected static ?string $breadcrumb = 'Список';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Новый тип оплаты')
                ->modalHeading('Новый тип оплаты')
                ->modalSubmitActionLabel('Создать')
                ->createAnother(false)
                ->modalCancelActionLabel('Отмена')
        ];
    }
}
