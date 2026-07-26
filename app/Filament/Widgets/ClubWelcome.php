<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\CashBook;
use App\Filament\Resources\Evenings\EveningResource;
use App\Filament\Resources\Players\PlayerResource;
use Filament\Widgets\Widget;

class ClubWelcome extends Widget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.club-welcome';

    protected function getViewData(): array
    {
        return [
            'cashBookUrl' => CashBook::getUrl(),
            'createEveningUrl' => EveningResource::getUrl('create'),
            'createPlayerUrl' => PlayerResource::getUrl('create'),
            'month' => now()->locale('ru')->translatedFormat('F Y'),
            'userName' => auth()->user()?->name,
        ];
    }
}
