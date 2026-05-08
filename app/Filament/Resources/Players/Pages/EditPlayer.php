<?php

namespace App\Filament\Resources\Players\Pages;

use App\Filament\Resources\Players\PlayerResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPlayer extends EditRecord
{
    protected static string $resource = PlayerResource::class;
    protected static ?string $breadcrumb = 'Редактировать';

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Детали'),
            DeleteAction::make()
                ->label('Удалить')
                ->modalHeading('Удаление игрока')
                ->modalDescription('Вы уверены, что хотите удалить игрока?')
                ->modalSubmitActionLabel('Удалить')
                ->modalCancelActionLabel('Отмена'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Сохранить'),

            $this->getCancelFormAction()
                ->label('Отмена'),
        ];
    }

    public function getTitle(): string
    {
        return $this->record->nickname;
    }
}
