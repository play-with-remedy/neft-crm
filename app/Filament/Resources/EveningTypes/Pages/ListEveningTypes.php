<?php

namespace App\Filament\Resources\EveningTypes\Pages;

use App\Filament\Resources\EveningTypes\EveningTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEveningTypes extends ListRecords
{
    protected static string $resource = EveningTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
