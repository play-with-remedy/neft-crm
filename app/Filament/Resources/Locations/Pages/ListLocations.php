<?php

namespace App\Filament\Resources\Locations\Pages;

use App\Filament\Resources\Locations\LocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLocations extends ListRecords
{
    protected static string $resource = LocationResource::class;

    protected static ?string $title = 'Адреса';

    protected static ?string $breadcrumb = 'Список';

    public function boot(): void
    {
        app()->setLocale('ru');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Добавить локацию')
                ->modalHeading('Новая локация')->modalWidth('3xl')
                ->modalSubmitActionLabel('Создать')->modalCancelActionLabel('Отмена')
                ->createAnother(false)->successNotificationTitle('Локация добавлена'),
        ];
    }
}
