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
use Filament\Tables\Filters\SelectFilter;
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
        [$activityPeriodFrom, $activityPeriodUntil] = $this->activityPeriod();

        return $table
            ->query(fn (): Builder => Player::query()
                ->select('players.*')
                ->with('source:id,name')
                ->withCount([
                    'participations as visits_count',
                    'participations as recent_visits_count' => fn (Builder $query): Builder => $query
                        ->whereHas('evening', fn (Builder $query): Builder => $query
                            ->whereBetween('played_at', [$activityPeriodFrom, $activityPeriodUntil])),
                ])
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
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('visits_count', $direction))
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
                    ->state(fn (Player $record): string => $record->activity_status_label)
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderByRaw(
                            "CASE
                                WHEN manual_activity_status = ? THEN 3
                                WHEN recent_visits_count >= ? THEN 2
                                ELSE 1
                            END {$direction}",
                            [
                                Player::MANUAL_ACTIVITY_STATUS_SEASON_PLAYER,
                                Player::CLUB_PLAYER_VISITS_THRESHOLD,
                            ],
                        ))
                    ->formatStateUsing(fn (string $state): HtmlString => new HtmlString(
                        view('filament.pages.partials.player-activity-status-badge', [
                            'label' => $state,
                        ])->render()
                    )),

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
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('ltv_total', $direction))
                    ->extraCellAttributes(['class' => 'player-analytics-centered-cell']),

                TextColumn::make('duration')
                    ->label('Продолжительность')
                    ->state(fn (Player $record): string => $this->formatDuration($record))
                    ->extraCellAttributes(['class' => 'player-analytics-centered-cell']),
            ])
            ->filters([
                SelectFilter::make('funnel_status')
                    ->label('Статус')
                    ->placeholder('Все статусы')
                    ->options([
                        'none' => 'Без статуса',
                        'new' => 'Новый',
                        'returned' => 'Вернулся',
                        'interested' => 'Заинтересован',
                        'engaged' => 'Вовлечён',
                        'contender' => 'Претендент',
                        'active' => 'Активный',
                        'regular' => 'Постоянный',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $this->filterByFunnelStatus(
                        $query,
                        $data['value'] ?? null,
                    )),

                SelectFilter::make('activity_status')
                    ->label('Статус активности')
                    ->placeholder('Все статусы активности')
                    ->options([
                        'season_player' => 'Игрок сезона',
                        'club_player' => 'Клубный игрок',
                        'club_guest' => 'Гость клуба',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $this->filterByActivityStatus(
                        $query,
                        $data['value'] ?? null,
                    )),
            ])
            ->defaultSort('visits_count', 'desc');
    }

    private function filterByFunnelStatus(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            'none' => $query->doesntHave('participations'),
            'new' => $query->has('participations', '=', 1),
            'returned' => $query->has('participations', '=', 2),
            'interested' => $query->has('participations', '=', 3),
            'engaged' => $query->has('participations', '=', 4),
            'contender' => $query
                ->has('participations', '>=', 5)
                ->has('participations', '<=', 9),
            'active' => $query
                ->has('participations', '>=', 10)
                ->has('participations', '<=', 20),
            'regular' => $query->has('participations', '>=', 21),
            default => $query,
        };
    }

    private function filterByActivityStatus(Builder $query, ?string $status): Builder
    {
        [$from, $until] = $this->activityPeriod();
        $recentVisits = fn (Builder $query): Builder => $query
            ->whereHas('evening', fn (Builder $query): Builder => $query
                ->whereBetween('played_at', [$from, $until]));

        return match ($status) {
            'season_player' => $query->where(
                'manual_activity_status',
                Player::MANUAL_ACTIVITY_STATUS_SEASON_PLAYER,
            ),
            'club_player' => $query
                ->whereNull('manual_activity_status')
                ->whereHas(
                    'participations',
                    $recentVisits,
                    '>=',
                    Player::CLUB_PLAYER_VISITS_THRESHOLD,
                ),
            'club_guest' => $query
                ->whereNull('manual_activity_status')
                ->whereHas(
                    'participations',
                    $recentVisits,
                    '<',
                    Player::CLUB_PLAYER_VISITS_THRESHOLD,
                ),
            default => $query,
        };
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function activityPeriod(): array
    {
        return [now()->startOfMonth()->subMonthsNoOverflow(4), now()];
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
