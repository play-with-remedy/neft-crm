<?php

namespace App\Filament\Resources\Evenings\Pages;

use App\Filament\Resources\Evenings\EveningResource;
use App\Services\SeasonalCaseService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEvening extends EditRecord
{
    protected ?bool $hasDatabaseTransactions = true;

    protected static string $resource = EveningResource::class;

    protected static ?string $breadcrumb = 'Редактировать';

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        app(SeasonalCaseService::class)->batch(fn () => parent::save($shouldRedirect, $shouldSendSavedNotification));
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Детали'),
            DeleteAction::make()
                ->databaseTransaction()
                ->label('Удалить')
                ->modalHeading('Удаление вечера')
                ->modalDescription('Вы уверены, что хотите удалить вечер?')
                ->modalSubmitActionLabel('Удалить')
                ->modalCancelActionLabel('Отмена')
                ->modalFooterActions(fn (DeleteAction $action): array => [
                    $action->getModalSubmitAction(),
                    $action->getModalCancelAction(),
                ]),
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
