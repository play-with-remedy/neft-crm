<?php

namespace App\Filament\Resources\EveningTypes\Pages;

use App\Filament\Resources\EveningTypes\EveningTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEveningType extends ViewRecord
{
    protected static string $resource = EveningTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
