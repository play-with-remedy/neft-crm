<?php

namespace App\Filament\Resources\Evenings\Pages;

use App\Filament\Resources\Evenings\EveningResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEvening extends ViewRecord
{
    protected static string $resource = EveningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
