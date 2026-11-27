<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Players\PlayerResource;
use App\Models\Evening;
use App\Models\Player;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class TodaysBirthdays extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected string $view = 'filament.widgets.todays-birthdays';

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return PlayerResource::canAccess();
    }

    public function table(Table $table): Table
    {
        $today = Evening::clubNow();

        return $table
            ->heading(fn (): View => view('filament.widgets.birthdays-heading', [
                'count' => $this->getTableRecords()->count(),
            ]))
            ->query(Player::query()
                ->where('birth_day', $today->day)
                ->where('birth_month', $today->month)
                // The importer uses 01.01.1900 for an unknown birth date.
                ->where(fn (Builder $query): Builder => $query
                    ->whereNull('birth_year')
                    ->orWhere('birth_year', '!=', 1900)
                    ->orWhere('birth_month', '!=', 1)
                    ->orWhere('birth_day', '!=', 1)))
            ->defaultSort('nickname')
            ->searchable(false)
            ->paginated(false)
            ->columns([
                TextColumn::make('position')->label('№')->rowIndex()->width('56px')->alignCenter(),
                TextColumn::make('nickname')->label('Игрок'),
                TextColumn::make('first_name')->label('Имя')->placeholder('—'),
                TextColumn::make('last_name')->label('Фамилия')->placeholder('—'),
            ])
            ->recordUrl(fn (Player $record): string => PlayerResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('Сегодня именинников нет')
            ->emptyStateIcon('heroicon-o-gift');
    }
}
