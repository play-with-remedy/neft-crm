<?php

namespace App\Filament\Resources\Players\Pages;

use App\Enums\AutumnCaseStatus;
use App\Filament\Resources\Players\PlayerResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlayerAutumnCase extends ViewRecord
{
    protected static string $resource = PlayerResource::class;

    protected static ?string $breadcrumb = 'Осеннее дело';

    public function getTitle(): string
    {
        return 'Осеннее дело: ' . $this->record->nickname;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('details')
                ->label('Карточка игрока')
                ->icon('heroicon-o-user')
                ->color('gray')
                ->url(fn (): string => PlayerResource::getUrl('view', ['record' => $this->record])),

            Action::make('statistics')
                ->label('Статистика')
                ->icon('heroicon-o-chart-bar')
                ->color('primary')
                ->url(fn (): string => PlayerResource::getUrl('statistics', ['record' => $this->record])),
        ];
    }

    public function content(Schema $schema): Schema
    {
        $latestCaseId = $this->record->autumnCases()->max('id');

        return $schema->components([
            Section::make('История дел')
                ->description('Актуальное дело показано первым')
                ->schema([
                    RepeatableEntry::make('autumn_case_history')
                        ->hiddenLabel()
                        ->getStateUsing(fn () => $this->record->autumnCases()
                            ->with([
                                'campaign',
                                'participations' => fn ($query) => $query
                                    ->with(['evening', 'paymentType'])
                                    ->join('evenings', 'evenings.id', '=', 'evening_participants.evening_id')
                                    ->orderByDesc('evening_participants.is_autumn_reward')
                                    ->orderByDesc('evening_participants.autumn_case_visit_number')
                                    ->orderByDesc('evenings.played_at')
                                    ->orderByDesc('evening_participants.id')
                                    ->select('evening_participants.*'),
                            ])
                            ->orderByDesc('id')
                            ->get())
                        ->schema([
                            Section::make(fn ($record): string => 'Дело № ' . ($record?->number ?? '—'))
                                ->description(fn ($record): string => $record
                                    ? $record->started_at->format('d.m.Y')
                                        . '–'
                                        . ($record->qualified_at ?? $record->deadline_at)->format('d.m.Y')
                                    : '')
                                ->icon('/images/autumn-leaf.svg?v=3')
                                ->schema([
                                    TextEntry::make('number')
                                        ->label('Номер дела')
                                        ->formatStateUsing(fn ($state): string => "№ {$state}"),

                                    TextEntry::make('campaign.name')
                                        ->label('Кампания'),

                                    TextEntry::make('status')
                                        ->label('Статус')
                                        ->formatStateUsing(fn (AutumnCaseStatus $state): string => $state->label())
                                        ->badge()
                                        ->color(fn (AutumnCaseStatus $state): string => $state->color()),

                                    TextEntry::make('progress')
                                        ->label('Прогресс')
                                        ->formatStateUsing(fn ($state): string => "{$state} из 5"),

                                    TextEntry::make('started_at')
                                        ->label('Открыто')
                                        ->date('d.m.Y'),

                                    TextEntry::make('deadline_at')
                                        ->label('Дедлайн')
                                        ->date('d.m.Y'),

                                    RepeatableEntry::make('participations')
                                        ->label('История посещений')
                                        ->schema([
                                            TextEntry::make('evening.played_at')
                                                ->label('Дата')
                                                ->date('d.m.Y'),

                                            TextEntry::make('visit_role')
                                                ->label('Посещение')
                                                ->state(fn ($record): string => $record->is_autumn_reward
                                                    ? 'Наградное'
                                                    : 'Визит № ' . $record->autumn_case_visit_number)
                                                ->badge()
                                                ->color(fn ($record): string => $record->is_autumn_reward
                                                    ? 'success'
                                                    : 'info'),

                                            TextEntry::make('paymentType.type')
                                                ->label('Тип оплаты')
                                                ->placeholder('—'),

                                            TextEntry::make('paid_amount')
                                                ->label('Оплата')
                                                ->formatStateUsing(fn ($state): string => number_format(
                                                    (float) $state,
                                                    2,
                                                    ',',
                                                    ' ',
                                                ) . ' BYN'),
                                        ])
                                        ->columns(4)
                                        ->columnSpanFull(),
                                ])
                                ->columns(4)
                                ->collapsible()
                                ->collapsed(fn ($record): bool => $record?->getKey() !== $latestCaseId)
                                ->columnSpanFull(),
                        ])
                        ->contained(false),
                ])
                ->columnSpanFull(),
        ]);
    }
}
