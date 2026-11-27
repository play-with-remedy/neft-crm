<?php

namespace App\Filament\Resources\Evenings\Pages;

use App\Filament\Resources\Evenings\EveningResource;
use App\Services\SeasonalCaseService;
use Filament\Resources\Pages\CreateRecord;

class CreateEvening extends CreateRecord
{
    protected ?bool $hasDatabaseTransactions = true;

    protected static string $resource = EveningResource::class;

    protected static ?string $title = 'Новый вечер';

    protected static ?string $breadcrumb = 'Создать';

    public function create(bool $another = false): void
    {
        app(SeasonalCaseService::class)->batch(fn () => parent::create($another));
    }

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
