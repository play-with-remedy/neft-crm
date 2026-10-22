<?php

namespace App\Filament\Resources\EveningLockPeriods\Pages;

use App\Filament\Resources\EveningLockPeriods\EveningLockPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListEveningLockPeriods extends ListRecords
{
    protected static string $resource = EveningLockPeriodResource::class;

    protected static ?string $title = 'Закрытые периоды';

    protected static ?string $breadcrumb = 'Список';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Закрыть период')
                ->modalHeading('Закрыть период')
                ->modalDescription('Обычный админ не сможет создавать, редактировать или удалять вечера в выбранном диапазоне дат.')
                ->modalSubmitActionLabel('Закрыть период')
                ->modalCancelActionLabel('Отмена')
                ->createAnother(false)
                ->mutateDataUsing(fn (array $data): array => [
                    ...$data,
                    'locked_by_user_id' => Auth::id(),
                ]),
        ];
    }
}
