<?php

namespace App\Filament\Resources\Evenings\Schemas;

use App\Models\PaymentType;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\Str;

class EveningForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основное')
                    ->schema([
                        Select::make('project_id')
                            ->label('Проект')
                            ->relationship('project', 'name')
                            ->preload()
                            ->nullable(),

                        Select::make('evening_type_id')
                            ->label('Тип вечера')
                            ->relationship('eveningType', 'name')
                            ->preload()
                            ->nullable(),

                        DatePicker::make('played_at')
                            ->label('Дата проведения')
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Расходы')
                    ->schema([
                        Repeater::make('expenses')
                            ->relationship()
                            ->defaultItems(0)
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Статья расходов'),
                                TableColumn::make('Сумма')->width('160px'),
                            ])
                            ->schema([
                                Select::make('expense_category_id')
                                    ->hiddenLabel()
                                    ->relationship('category', 'name')
                                    ->preload()
                                    ->required(),

                                TextInput::make('amount')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required(),
                            ])
                            ->compact()
                            ->addActionLabel('Добавить расход')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),

                Section::make('Команда вечера')
                    ->schema([
                        Repeater::make('staff')
                            ->relationship()
                            ->defaultItems(0)
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Человек'),
                                TableColumn::make('Роль')->width('180px'),
                                TableColumn::make('Зарплата')->width('160px'),
                            ])
                            ->schema([
                                Select::make('host_id')
                                    ->hiddenLabel()
                                    ->relationship('host', 'nickname')
                                    ->preload()
                                    ->required(),

                                Select::make('role')
                                    ->hiddenLabel()
                                    ->options([
                                        'host' => 'Ведущий',
                                        'admin' => 'Админ',
                                        'manager' => 'Менеджер',
                                        'supervisor' => 'Супервайзер',
                                    ])
                                    ->required(),

                                TextInput::make('salary')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required(),
                            ])
                            ->compact()
                            ->addActionLabel('Добавить человека')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),

                Section::make('Участники')
                    ->id('evening-participants-section')
                    ->schema([
                        Section::make('Массовое добавление участников')
                            ->description('Укажите общие параметры для новых строк. После добавления каждую строку можно изменить отдельно.')
                            ->schema([
                                TextInput::make('participants_batch_count')
                            ->label('Количество участников')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(1)
                            ->live()
                            ->dehydrated(false),

                                Select::make('participants_batch_payment_type_id')
                            ->label('Тип оплаты')
                            ->options(fn (): array => PaymentType::query()
                                ->orderBy('id')
                                ->pluck('type', 'id')
                                ->all())
                            ->default(fn (): ?int => PaymentType::query()
                                ->where('type', 'Наличные')
                                ->value('id'))
                            ->selectablePlaceholder(false)
                            ->preload()
                            ->live()
                            ->dehydrated(false),

                                TextInput::make('participants_batch_paid_amount')
                            ->label('Сумма оплаты')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->live()
                            ->dehydrated(false),

                                Actions::make([
                                    Action::make('add_participants_batch')
                                ->label('Добавить строки')
                                ->icon('heroicon-o-user-plus')
                                ->action(function (Get $get, Set $set): void {
                                    $count = max(1, min(100, (int) $get('participants_batch_count')));
                                    $paymentTypeId = (int) $get('participants_batch_payment_type_id');
                                    $paidAmount = $get('participants_batch_paid_amount') ?? 0;
                                    $participants = $get('participants') ?? [];

                                    if (! PaymentType::query()->whereKey($paymentTypeId)->exists()) {
                                        Notification::make()
                                            ->title('Выберите тип оплаты')
                                            ->danger()
                                            ->send();

                                        return;
                                    }

                                    for ($index = 0; $index < $count; $index++) {
                                        $participants[Str::uuid()->toString()] = [
                                            'player_id' => null,
                                            'payment_type_id' => $paymentTypeId,
                                            'paid_amount' => $paidAmount,
                                            'is_new_player' => false,
                                            'is_full_payment' => true,
                                            'note' => null,
                                        ];
                                    }

                                    $set('participants', $participants);

                                    Notification::make()
                                        ->title("Добавлено строк: {$count}")
                                        ->success()
                                        ->send();
                                        }),
                                ])
                                    ->alignEnd()
                                    ->verticallyAlignEnd(),
                            ])
                            ->columns(4)
                            ->compact()
                            ->columnSpanFull(),

                        Section::make('Список участников')
                            ->schema([
                                Placeholder::make('participants_total_paid')
                                    ->label('Общая сумма взносов')
                                    ->content(function (Get $get): string {
                                        $total = collect($get('participants') ?? [])
                                            ->sum(fn (array $participant): float => (float) ($participant['paid_amount'] ?? 0));

                                        return number_format($total, 2, ',', ' ') . ' BYN';
                                    }),

                                Repeater::make('participants')
                            ->relationship()
                            ->defaultItems(0)
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('#')->width('60px'),
                                TableColumn::make('Игрок'),
                                TableColumn::make('Тип оплаты')->width('180px'),
                                TableColumn::make('Оплата')->width('140px'),
                                TableColumn::make('Новый')->width('100px'),
                                TableColumn::make('Полная')->width('100px'),
                                TableColumn::make('Примечание'),
                            ])
                            ->schema([
                                Placeholder::make('row_number')
                                    ->hiddenLabel()
                                    ->content(function ($component) {
                                        $repeater = $component->getContainer()->getParentComponent();

                                        $state = $repeater->getState() ?? [];
                                        $keys = array_keys($state);

                                        $statePath = $component->getStatePath();
                                        $repeaterStatePath = $repeater->getStatePath();

                                        $itemKey = str($statePath)
                                            ->after($repeaterStatePath . '.')
                                            ->beforeLast('.')
                                            ->toString();

                                        $index = array_search($itemKey, $keys, true);

                                        return $index === false ? '' : $index + 1;
                                    }),

                                Select::make('player_id')
                                    ->hiddenLabel()
                                    ->relationship('player', 'nickname')
                                    ->searchable()
                                    ->preload(false)
                                    ->distinct()
                                    ->validationMessages([
                                        'distinct' => 'Этот игрок уже добавлен в участники вечера.',
                                    ])
                                    ->required(),

                                Select::make('payment_type_id')
                                    ->hiddenLabel()
                                    ->relationship('paymentType', 'type')
                                    ->default(fn (): ?int => PaymentType::query()
                                        ->where('type', 'Наличные')
                                        ->value('id'))
                                    ->selectablePlaceholder(false)
                                    ->preload()
                                    ->required(),

                                TextInput::make('paid_amount')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->default(0)
                                    ->live(debounce: 400)
                                    ->required(),

                                Toggle::make('is_new_player')
                                    ->hiddenLabel()
                                    ->default(false)
                                    ->inline(false),

                                Toggle::make('is_full_payment')
                                    ->hiddenLabel()
                                    ->default(true)
                                    ->inline(false),

                                Textarea::make('note')
                                    ->hiddenLabel()
                                    ->rows(1)
                                    ->placeholder('Комментарий'),
                            ])
                            ->compact()
                            ->addActionLabel('Добавить участника')
                            ->addAction(fn (Action $action): Action => $action->color('primary'))
                            ->columnSpanFull(),
                            ])
                            ->compact()
                            ->columnSpanFull(),
                    ])
                    ->columns(4)
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
    }
}
