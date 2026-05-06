<?php

namespace App\Filament\Resources\Locations\Pages;

use App\Filament\Resources\Locations\LocationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLocation extends EditRecord
{
    protected static string $resource = LocationResource::class;

    protected static ?string $breadcrumb = 'Редактировать';

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Детали'),
            DeleteAction::make()
                ->label('Удалить'),
        ];
    }

    public function getTitle(): string
    {
        return $this->record->name;
    }
}
