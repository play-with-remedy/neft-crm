<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Players\PlayerResource;
use App\Models\Evening;
use App\Models\EveningParticipant;
use App\Models\EveningType;
use App\Models\Player;
use App\Models\Project;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class PlayerRating extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $navigationLabel = 'Рейтинг игроков';

    protected static ?string $title = 'Рейтинг игроков';

    protected static UnitEnum | string | null $navigationGroup = 'Отчеты';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.pages.player-rating';

    public function getSubheading(): ?string
    {
        return 'Посещения и оплаты игроков за всё время';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getPlayersQuery())
            ->columns([
                TextColumn::make('position')
                    ->label('№')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('nickname')
                    ->label('Игрок')
                    ->searchable()
                    ->sortable()
                    ->url(
                        fn (Player $record): string => PlayerResource::getUrl(
                            'statistics',
                            ['record' => $record],
                        )
                    ),

                TextColumn::make('participations_count')
                    ->label('Посещений')
                    ->numeric(decimalPlaces: 0)
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('participations_sum_paid_amount')
                    ->label('Сумма оплаты')
                    ->formatStateUsing(
                        fn ($state): string => number_format((int) $state, 0, ',', ' ') . ' BYN'
                    )
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('statistics_first_visit')
                    ->label('Первый визит')
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder('—')
                    ->alignCenter(),

                TextColumn::make('statistics_last_visit')
                    ->label('Последний визит')
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder('—')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('project')
                    ->label('По проекту')
                    ->placeholder('Все проекты')
                    ->options(fn (): array => Project::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query): Builder => $query),

                SelectFilter::make('evening_type')
                    ->label('По типу')
                    ->placeholder('Все типы')
                    ->options(fn (): array => EveningType::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query): Builder => $query),

                SelectFilter::make('month')
                    ->label('По месяцу')
                    ->placeholder('Все месяцы')
                    ->options(fn (): array => $this->monthOptions())
                    ->query(fn (Builder $query): Builder => $query),
            ])
            ->filtersApplyAction(
                fn (Action $action): Action => $action->label('Применить')
            )
            ->filtersRemoveAllAction(
                fn (Action $action): Action => $action
                    ->label('Сбросить все фильтры')
                    ->tooltip('Сбросить все фильтры')
            )
            ->filtersTriggerAction(function (Action $action) use ($table): Action {
                return $action
                    ->label('Фильтры')
                    ->modalCancelActionLabel('Закрыть')
                    ->extraModalFooterActions([
                        $table->getFiltersApplyAction()->close(),
                        Action::make('resetFilters')
                            ->label('Сбросить')
                            ->color('danger')
                            ->action('resetTableFiltersForm')
                            ->button(),
                    ]);
            })
            ->defaultSort('participations_sum_paid_amount', 'desc')
            ->emptyStateHeading('Игроки не найдены')
            ->emptyStateDescription('Измените параметры фильтра или добавьте посещения игроков.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([10, 25, 50, 100]);
    }

    private function getPlayersQuery(): Builder
    {
        $firstVisit = EveningParticipant::query()
            ->selectRaw('MIN(evenings.played_at)')
            ->join('evenings', 'evenings.id', '=', 'evening_participants.evening_id')
            ->whereColumn('evening_participants.player_id', 'players.id');

        $this->applyJoinedEveningFilters($firstVisit);

        $lastVisit = EveningParticipant::query()
            ->selectRaw('MAX(evenings.played_at)')
            ->join('evenings', 'evenings.id', '=', 'evening_participants.evening_id')
            ->whereColumn('evening_participants.player_id', 'players.id');

        $this->applyJoinedEveningFilters($lastVisit);

        return Player::query()
            ->whereHas(
                'participations',
                fn (Builder $query): Builder => $this->applyParticipationFilters($query),
            )
            ->withCount([
                'participations' => fn (Builder $query): Builder => $this->applyParticipationFilters($query),
            ])
            ->withSum([
                'participations' => fn (Builder $query): Builder => $this->applyParticipationFilters($query),
            ], 'paid_amount')
            ->addSelect([
                'statistics_first_visit' => $firstVisit,
                'statistics_last_visit' => $lastVisit,
            ]);
    }

    private function applyParticipationFilters(Builder $query): Builder
    {
        [$projectId, $eveningTypeId, $periodStart, $periodEnd] = $this->activeFilters();

        if (! $projectId && ! $eveningTypeId && ! $periodStart) {
            return $query;
        }

        return $query->whereHas('evening', function (Builder $query) use (
            $projectId,
            $eveningTypeId,
            $periodStart,
            $periodEnd,
        ): void {
            $query
                ->when($projectId, fn (Builder $query): Builder => $query->where('project_id', $projectId))
                ->when($eveningTypeId, fn (Builder $query): Builder => $query->where('evening_type_id', $eveningTypeId))
                ->when($periodStart, fn (Builder $query): Builder => $query
                    ->where('played_at', '>=', $periodStart)
                    ->where('played_at', '<', $periodEnd));
        });
    }

    private function applyJoinedEveningFilters(Builder $query): void
    {
        [$projectId, $eveningTypeId, $periodStart, $periodEnd] = $this->activeFilters();

        $query
            ->when($projectId, fn (Builder $query): Builder => $query->where('evenings.project_id', $projectId))
            ->when($eveningTypeId, fn (Builder $query): Builder => $query->where('evenings.evening_type_id', $eveningTypeId))
            ->when($periodStart, fn (Builder $query): Builder => $query
                ->where('evenings.played_at', '>=', $periodStart)
                ->where('evenings.played_at', '<', $periodEnd));
    }

    private function activeFilters(): array
    {
        $projectId = $this->getTableFilterState('project')['value'] ?? null;
        $eveningTypeId = $this->getTableFilterState('evening_type')['value'] ?? null;
        $month = $this->getTableFilterState('month')['value'] ?? null;

        $periodStart = filled($month)
            ? Carbon::createFromFormat('!Y-m', $month)->startOfMonth()
            : null;

        return [
            filled($projectId) ? (int) $projectId : null,
            filled($eveningTypeId) ? (int) $eveningTypeId : null,
            $periodStart,
            $periodStart?->copy()->addMonth(),
        ];
    }

    private function monthOptions(): array
    {
        return Evening::query()
            ->orderByDesc('played_at')
            ->pluck('played_at')
            ->map(fn ($date): Carbon => Carbon::parse($date)->startOfMonth())
            ->unique(fn (Carbon $date): string => $date->format('Y-m'))
            ->mapWithKeys(fn (Carbon $date): array => [
                $date->format('Y-m') => Str::ucfirst($date->locale('ru')->translatedFormat('F Y')),
            ])
            ->all();
    }
}
