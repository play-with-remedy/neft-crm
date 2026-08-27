<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Players\PlayerResource;
use App\Models\EveningParticipant;
use App\Models\Player;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use UnitEnum;

class PlayerAnalytics extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?string $slug = 'player-analytics';

    protected static ?string $navigationLabel = 'Аналитика игроков';

    protected static ?string $title = 'Аналитика игроков';

    protected static UnitEnum | string | null $navigationGroup = 'Отчеты';

    protected static ?int $navigationSort = 35;

    protected string $view = 'filament.pages.player-analytics';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Player::query()
                ->select('players.*')
                ->with('source:id,name')
                ->withCount(['participations as visits_count'])
                ->withSum(['participations as ltv_total'], 'paid_amount')
                ->addSelect([
                    'last_visit_at' => EveningParticipant::query()
                        ->join('evenings', 'evenings.id', '=', 'evening_participants.evening_id')
                        ->whereColumn('evening_participants.player_id', 'players.id')
                        ->selectRaw('MAX(evenings.played_at)'),
                ]))
            ->columns([
                TextColumn::make('nickname')
                    ->label('Никнейм')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Player $record): string => PlayerResource::getUrl('view', ['record' => $record])),

                TextColumn::make('status')
                    ->label('Статус')
                    ->state(fn (Player $record): string => $this->funnelStatusKey((int) $record->visits_count))
                    ->formatStateUsing(fn (string $state): HtmlString => new HtmlString(
                        view(
                            'filament.pages.partials.player-status-badge',
                            [
                                'stageKey' => $state,
                                'label' => $this->funnelStatusLabel($state),
                            ],
                        )->render()
                    )),

                TextColumn::make('activity_status')
                    ->label('Статус активности')
                    ->state(fn (): string => '—'),

                TextColumn::make('source.name')
                    ->label('Источник')
                    ->placeholder('—'),

                TextColumn::make('first_visit_at')
                    ->label('Первый визит')
                    ->date('d.m.Y')
                    ->sortable()
                    ->extraCellAttributes(['class' => 'player-analytics-centered-cell'])
                    ->placeholder('—'),

                TextColumn::make('last_visit_at')
                    ->label('Последний визит')
                    ->date('d.m.Y')
                    ->sortable()
                    ->extraCellAttributes(['class' => 'player-analytics-centered-cell'])
                    ->placeholder('—'),

                TextColumn::make('visits_count')
                    ->label('Визитов')
                    ->numeric(decimalPlaces: 0)
                    ->sortable()
                    ->extraCellAttributes(['class' => 'player-analytics-centered-cell']),

                TextColumn::make('ltv_total')
                    ->label('LTV всего')
                    ->state(fn (Player $record): float => (float) $record->ltv_total)
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', ' ') . ' BYN')
                    ->extraCellAttributes(['class' => 'player-analytics-centered-cell']),

                TextColumn::make('average_check')
                    ->label('Средний чек')
                    ->state(fn (Player $record): ?float => (int) $record->visits_count === 0
                        ? null
                        : (float) $record->ltv_total / (int) $record->visits_count)
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', ' ') . ' BYN')
                    ->extraCellAttributes(['class' => 'player-analytics-centered-cell'])
                    ->placeholder('—'),

                TextColumn::make('duration')
                    ->label('Продолжительность')
                    ->state(fn (Player $record): string => $this->formatDuration($record))
                    ->extraCellAttributes(['class' => 'player-analytics-centered-cell']),
            ])
            ->defaultSort('visits_count', 'desc');
    }

    private function formatDuration(Player $player): string
    {
        if (! $player->first_visit_at || ! $player->last_visit_at) {
            return '—';
        }

        $interval = Carbon::parse($player->first_visit_at)
            ->startOfDay()
            ->diff(Carbon::parse($player->last_visit_at)->startOfDay());

        $parts = [];

        if ($interval->y > 0) {
            $parts[] = $interval->y . ' ' . $this->russianPlural($interval->y, 'год', 'года', 'лет');
        }

        if ($interval->m > 0) {
            $parts[] = $interval->m . ' ' . $this->russianPlural($interval->m, 'месяц', 'месяца', 'месяцев');
        }

        if ($interval->d > 0 || $parts === []) {
            $parts[] = $interval->d . ' ' . $this->russianPlural($interval->d, 'день', 'дня', 'дней');
        }

        return implode(' ', $parts);
    }

    private function funnelStatusKey(int $visits): string
    {
        return match (true) {
            $visits >= 21 => 'regular',
            $visits >= 10 => 'active',
            $visits >= 5 => 'contender',
            $visits === 4 => 'engaged',
            $visits === 3 => 'interested',
            $visits === 2 => 'returned',
            $visits === 1 => 'new',
            default => 'none',
        };
    }

    private function funnelStatusLabel(string $status): string
    {
        return match ($status) {
            'new' => 'Новый',
            'returned' => 'Вернулся',
            'interested' => 'Заинтересован',
            'engaged' => 'Вовлечён',
            'contender' => 'Претендент',
            'active' => 'Активный',
            'regular' => 'Постоянный',
            default => '—',
        };
    }

    private function russianPlural(int $number, string $one, string $few, string $many): string
    {
        $number = abs($number) % 100;
        $lastDigit = $number % 10;

        if ($number > 10 && $number < 20) {
            return $many;
        }

        return match (true) {
            $lastDigit === 1 => $one,
            $lastDigit >= 2 && $lastDigit <= 4 => $few,
            default => $many,
        };
    }
}
