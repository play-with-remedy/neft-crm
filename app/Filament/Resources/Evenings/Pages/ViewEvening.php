<?php

namespace App\Filament\Resources\Evenings\Pages;

use App\Filament\Resources\Evenings\Actions\EveningLockActions;
use App\Filament\Resources\Evenings\EveningResource;
use App\Services\EveningLockService;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEvening extends ViewRecord
{
    protected static string $resource = EveningResource::class;

    protected static ?string $breadcrumb = 'Детали';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Редактировать'),
            EveningLockActions::lock(),
            EveningLockActions::unlock(),
        ];
    }

    public function getSubheading(): ?string
    {
        return app(EveningLockService::class)->description($this->record);
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
