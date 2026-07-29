<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Evenings\EveningResource;
use App\Models\EveningParticipant;
use App\Models\Player;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PlayerVisitHistory extends TableWidget
{
    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    public ?Player $record = null;

    public function table(Table $table): Table
    {
        return $table
            ->heading('История посещений')
            ->description('Все вечера, в которых участвовал игрок')
            ->query(
                EveningParticipant::query()
                    ->where('player_id', $this->record?->getKey())
                    ->with([
                        'evening.project',
                        'evening.eveningType',
                        'paymentType',
                    ])
            )
            ->columns([
                TextColumn::make('evening.played_at')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('evening.project.name')
                    ->label('Проект')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('evening.eveningType.name')
                    ->label('Тип вечера')
                    ->placeholder('—'),

                TextColumn::make('paid_amount')
                    ->label('Оплачено')
                    ->formatStateUsing(fn ($state): string => number_format((int) $state, 0, ',', ' ') . ' BYN')
                    ->weight('bold')
                    ->color('primary')
                    ->alignEnd(),

                TextColumn::make('paymentType.type')
                    ->label('Тип оплаты')
                    ->badge()
                    ->placeholder('—'),
            ])
            ->recordUrl(
                fn (EveningParticipant $participation): string => EveningResource::getUrl(
                    'view',
                    ['record' => $participation->evening_id],
                )
            )
            ->defaultSort('evening.played_at', 'desc')
            ->emptyStateHeading('Посещений пока нет')
            ->emptyStateDescription('История появится после добавления игрока в вечер.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50]);
    }
}
