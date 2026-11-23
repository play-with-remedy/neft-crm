<?php

namespace App\Filament\Resources\Locations\Pages;

use App\Filament\Resources\Locations\LocationResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;

class ListLocations extends ListRecords
{
    protected static string $resource = LocationResource::class;

    protected static ?string $title = 'Локации';

    protected static ?string $breadcrumb = 'Список';

    public function boot(): void
    {
        app()->setLocale('ru');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Добавить локацию')
                ->databaseTransaction()
                ->extraModalWindowAttributes(['class' => 'fi-location-modal'])
                ->modalHeading('Новая локация')->modalWidth('3xl')
                ->modalSubmitActionLabel('Создать')->modalCancelActionLabel('Отмена')
                ->modalFooterActionsAlignment(Alignment::End)
                ->modalFooterActions(fn (Action $action): array => [$action->getModalCancelAction(), $action->getModalSubmitAction()])
                ->createAnother(false)->successNotificationTitle('Локация добавлена'),
        ];
    }
}
