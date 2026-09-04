<?php

namespace App\Filament\Pages;

use App\Enums\AutumnCaseStatus;
use App\Filament\Resources\Players\PlayerResource;
use App\Models\AutumnCampaign;
use App\Models\AutumnCase;
use App\Models\EveningParticipant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AutumnCampaignDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | BackedEnum | null $navigationIcon = '/images/autumn-leaf.svg?v=3';

    protected static ?string $slug = 'autumn-campaign';

    protected static ?string $navigationLabel = 'Осеннее дело';

    protected static ?string $title = 'Осеннее дело';

    protected static UnitEnum | string | null $navigationGroup = 'Клуб';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.autumn-campaign-dashboard';

    public ?int $campaignId = null;

    public function mount(): void
    {
        $this->campaignId = AutumnCampaign::query()
            ->orderByRaw('CASE WHEN starts_at <= ? AND ends_at >= ? THEN 0 WHEN starts_at > ? THEN 1 ELSE 2 END', [
                today()->toDateString(),
                today()->toDateString(),
                today()->toDateString(),
            ])
            ->orderByDesc('starts_at')
            ->value('id');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => AutumnCase::query()
                ->where('autumn_campaign_id', $this->campaignId ?? 0)
                ->whereNotExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('autumn_cases as newer_cases')
                        ->whereColumn('newer_cases.autumn_campaign_id', 'autumn_cases.autumn_campaign_id')
                        ->whereColumn('newer_cases.player_id', 'autumn_cases.player_id')
                        ->whereColumn('newer_cases.number', '>', 'autumn_cases.number');
                })
                ->with(['player:id,nickname', 'campaign:id,name,ends_at'])
                ->withCount([
                    'participations as progress' => fn (Builder $query): Builder => $query
                        ->where('is_autumn_reward', false),
                ])
                ->addSelect([
                    'last_visit_at' => EveningParticipant::query()
                        ->join('evenings', 'evenings.id', '=', 'evening_participants.evening_id')
                        ->whereColumn('evening_participants.autumn_case_id', 'autumn_cases.id')
                        ->selectRaw('MAX(evenings.played_at)'),
                ]))
            ->columns([
                TextColumn::make('player.nickname')
                    ->label('Игрок')
                    ->searchable()
                    ->url(fn (AutumnCase $record): string => PlayerResource::getUrl(
                        'autumn-case',
                        ['record' => $record->player_id],
                    )),

                TextColumn::make('number')
                    ->label('Дело')
                    ->formatStateUsing(fn ($state): string => "№ {$state}")
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn (AutumnCaseStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (AutumnCaseStatus $state): string => $state->color())
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        $campaignEndsAt = AutumnCampaign::query()
                            ->whereKey($this->campaignId)
                            ->value('ends_at') ?? today()->toDateString();
                        $today = today()->toDateString();

                        return $query->orderByRaw(
                            "CASE
                                WHEN completed_at IS NOT NULL THEN 3
                                WHEN qualified_at IS NOT NULL AND ? > ? THEN 5
                                WHEN qualified_at IS NOT NULL THEN 2
                                WHEN deadline_at < ? THEN 4
                                ELSE 1
                            END {$direction}",
                            [$today, $campaignEndsAt, $today],
                        );
                    }),

                TextColumn::make('progress')
                    ->label('Прогресс')
                    ->formatStateUsing(fn ($state): string => min((int) $state, 5) . ' из 5')
                    ->sortable(),

                TextColumn::make('started_at')
                    ->label('Открыто')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('deadline_at')
                    ->label('Дедлайн')
                    ->date('d.m.Y'),

                TextColumn::make('days_remaining')
                    ->label('Осталось')
                    ->state(function (AutumnCase $record): string {
                        if ($record->status !== AutumnCaseStatus::InProgress) {
                            return '—';
                        }

                        $days = today()->diffInDays($record->deadline_at, false);

                        return $days < 0 ? 'Просрочено' : $days . ' дн.';
                    }),

                TextColumn::make('last_visit_at')
                    ->label('Последний визит')
                    ->date('d.m.Y')
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Открыть')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (AutumnCase $record): string => PlayerResource::getUrl(
                        'autumn-case',
                        ['record' => $record->player_id],
                    )),
            ])
            ->defaultSort('progress', 'desc')
            ->emptyStateHeading('Дел пока нет')
            ->emptyStateDescription('Первое дело появится после посещения игрока во время кампании.');
    }

    /** @return array{campaign: ?AutumnCampaign, players: int, active: int, rewards: int, completed: int, expired: int} */
    public function getSummary(): array
    {
        $campaign = $this->campaignId
            ? AutumnCampaign::query()->find($this->campaignId)
            : null;

        $query = AutumnCase::query()
            ->where('autumn_campaign_id', $this->campaignId ?? 0);
        $today = today()->toDateString();
        $campaignHasEnded = $campaign?->ends_at?->lt(today()) ?? false;

        $expired = (clone $query)
            ->whereNull('qualified_at')
            ->whereNull('completed_at')
            ->whereDate('deadline_at', '<', $today)
            ->count();

        if ($campaignHasEnded) {
            $expired += (clone $query)
                ->whereNotNull('qualified_at')
                ->whereNull('completed_at')
                ->count();
        }

        return [
            'campaign' => $campaign,
            'players' => (clone $query)->distinct()->count('player_id'),
            'active' => (clone $query)
                ->whereNull('qualified_at')
                ->whereNull('completed_at')
                ->whereDate('deadline_at', '>=', $today)
                ->count(),
            'rewards' => $campaignHasEnded
                ? 0
                : (clone $query)
                    ->whereNotNull('qualified_at')
                    ->whereNull('completed_at')
                    ->count(),
            'completed' => (clone $query)->whereNotNull('completed_at')->count(),
            'expired' => $expired,
        ];
    }
}
