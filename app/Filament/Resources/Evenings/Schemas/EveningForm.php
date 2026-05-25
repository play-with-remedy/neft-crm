<?php

namespace App\Filament\Resources\Evenings\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                                        'manager' => 'Менджер',
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

                Section::make(fn ($get) => 'Участники: ' . count($get('participants') ?? []))
                    ->schema([
                        Repeater::make('participants')
                            ->relationship()
                            ->hiddenLabel()
                            ->itemNumbers()
                            ->table([
                                TableColumn::make('Игрок'),
                                TableColumn::make('Тип оплаты')->width('180px'),
                                TableColumn::make('Оплата')->width('140px'),
                                TableColumn::make('Новый')->width('100px'),
                                TableColumn::make('Полная')->width('100px'),
                                TableColumn::make('Примечание'),
                            ])
                            ->schema([
                                Select::make('player_id')
                                    ->hiddenLabel()
                                    ->relationship('player', 'nickname')
                                    ->searchable()
                                    ->preload(false)
                                    ->required(),

                                Select::make('payment_type_id')
                                    ->hiddenLabel()
                                    ->relationship('paymentType', 'type')
                                    ->preload()
                                    ->required(),

                                TextInput::make('paid_amount')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->default(0)
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
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
    }
}