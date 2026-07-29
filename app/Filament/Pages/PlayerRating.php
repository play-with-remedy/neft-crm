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
            ->query($this->getPlayersQuery())
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

        $lastVisit = EveningParticipant::query()
            ->selectRaw('MAX(evenings.played_at)')
            ->join('evenings', 'evenings.id', '=', 'evening_participants.evening_id')
            ->whereColumn('evening_participants.player_id', 'players.id');

        return Player::query()
            ->has('participations')
            ->withCount('participations')
            ->withSum('participations', 'paid_amount')
            ->addSelect([
                'statistics_first_visit' => $firstVisit,
                'statistics_last_visit' => $lastVisit,
            ]);
    }
}
