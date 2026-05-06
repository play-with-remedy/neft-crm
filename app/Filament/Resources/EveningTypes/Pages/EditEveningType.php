<?php

namespace App\Filament\Resources\EveningTypes\Pages;

use App\Filament\Resources\EveningTypes\EveningTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEveningType extends EditRecord
{
    protected static string $resource = EveningTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
