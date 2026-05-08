<?php

namespace App\Filament\Resources\Evenings\Pages;

use App\Filament\Resources\Evenings\EveningResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEvening extends EditRecord
{
    protected static string $resource = EveningResource::class;
    protected static ?string $breadcrumb = 'Редактировать';

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Детали'),
            DeleteAction::make()
                ->label('Удалить')
                ->modalHeading('Удаление вечера')
                ->modalDescription('Вы уверены, что хотите удалить вечер?')
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
        $this->record->loadMissing(['project', 'eveningType']);

        return collect([
            $this->record->project?->name,
            $this->record->eveningType?->name,
            $this->record->played_at?->format('d.m.Y'),
        ])
            ->filter()
            ->join(' • ');
    }
}
