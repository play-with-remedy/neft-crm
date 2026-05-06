<?php

namespace App\Filament\Resources\Evenings\Pages;

use App\Filament\Resources\Evenings\EveningResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEvening extends EditRecord
{
    protected static string $resource = EveningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
