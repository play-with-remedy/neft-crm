<?php

namespace App\Filament\Resources\Players;

use App\Filament\Resources\Players\Pages\CreatePlayer;
use App\Filament\Resources\Players\Pages\EditPlayer;
use App\Filament\Resources\Players\Pages\ListPlayers;
use App\Filament\Resources\Players\Pages\ViewPlayer;
use App\Filament\Resources\Players\Schemas\PlayerInfolist;
use App\Enums\NavigationGroup;
use App\Models\Player;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use UnitEnum;

class PlayerResource extends Resource
{
    protected static ?string $model = Player::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Игроки';
    protected static ?string $modelLabel = 'Игрок';
    protected static ?string $pluralModelLabel = 'Игроки';
    protected static ?string $recordTitleAttribute = 'nickname';
    
    protected static UnitEnum|string|null $navigationGroup = 'Клуб';
    protected static ?int $navigationSort = 1;

    private static function months(): array
    {
        return [
            1 => 'Январь',
            2 => 'Февраль',
            3 => 'Март',
            4 => 'Апрель',
            5 => 'Май',
            6 => 'Июнь',
            7 => 'Июль',
            8 => 'Август',
            9 => 'Сентябрь',
            10 => 'Октябрь',
            11 => 'Ноябрь',
            12 => 'Декабрь',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nickname')
                    ->label('Никнейм')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                TextInput::make('first_name')
                    ->label('Имя')
                    ->required()
                    ->maxLength(25)
                    ->rule('regex:/^[\p{L}\s-]+$/u')
                    ->validationMessages([
                        'regex' => 'Имя может содержать только буквы, пробел и дефис.',
                    ]),

                TextInput::make('last_name')
                    ->label('Фамилия')
                    ->maxLength(50)
                    ->rule('regex:/^[\p{L}\s-]+$/u')
                    ->validationMessages([
                        'regex' => 'Фамилия может содержать только буквы, пробел и дефис.',
                    ]),

                Select::make('gender')
                    ->label('Пол')
                    ->required()
                    ->options([
                        'male' => 'Мужской',
                        'female' => 'Женский',
                    ]),

                Fieldset::make('Дата рождения')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('birth_day')
                                    ->label('День')
                                    ->options(array_combine(range(1, 31), range(1, 31)))
                                    ->required()
                                    ->native(false),

                                Select::make('birth_month')
                                    ->label('Месяц')
                                    ->options(self::months())
                                    ->required()
                                    ->native(false),

                                TextInput::make('birth_year')
                                    ->label('Год')
                                    ->numeric()
                                    ->placeholder('Необязательно')
                                    ->minValue(1900)
                                    ->maxValue(now()->year),
                            ]),
                    ])
                    ->columnSpanFull(),

                TextInput::make('phone')
                    ->label('Телефон')
                    ->placeholder('+375336939589')
                    ->rule('regex:/^\+?\d+$/')
                    ->validationMessages([
                        'regex' => 'Телефон может содержать только цифры и один + в начале.',
                    ]),

                TextInput::make('telegram')
                    ->label('Telegram')
                    ->maxLength(50),

                Select::make('source_id')
                    ->label('Откуда узнали')
                    ->relationship('source', 'name')
                    ->preload(),

                DatePicker::make('first_visit_at')
                    ->label('Первое посещение')
                    ->minDate('1900-01-01')
                    ->maxDate(now()),

                Select::make('first_host_id')
                    ->label('Кто был ведущим')
                    ->relationship('firstHost', 'nickname')
                    ->preload(),

                Textarea::make('notes')
                    ->label('Комментарии')
                    ->rows(4)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PlayerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nickname')
                    ->label('Никнейм')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('first_name')
                    ->label('Имя')
                    ->searchable(),

                Tables\Columns\TextColumn::make('last_name')
                    ->label('Фамилия')
                    ->searchable(),

                Tables\Columns\TextColumn::make('birthday')
                    ->label('Дата рождения')
                    ->state(function ($record) {
                        $date = str_pad($record->birth_day, 2, '0', STR_PAD_LEFT)
                            . '.'
                            . str_pad($record->birth_month, 2, '0', STR_PAD_LEFT);

                        if ($record->birth_year) {
                            $date .= '.' . $record->birth_year;
                        }

                        return $date;
                    }),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),

                Tables\Columns\TextColumn::make('telegram')
                    ->label('Telegram')
                    ->searchable(),

                Tables\Columns\TextColumn::make('source.name')
                    ->label('Источник'),

                Tables\Columns\TextColumn::make('first_visit_at')
                    ->label('Первое посещение')
                    ->date(),

                Tables\Columns\TextColumn::make('firstHost.nickname')
                    ->label('Ведущий'),
            ])
            ->filters([
                Filter::make('birth_month_filter')
                    ->form([
                        Placeholder::make('birthday_filter_title')
                            ->hiddenLabel()
                            ->content('Фильтр по дню рождения'),

                        Select::make('birth_month')
                            ->label('Месяц рождения')
                            ->placeholder('Например: Май')
                            ->options(self::months())
                            ->native(false),
                    ])
                    ->indicateUsing(function (array $data): array {
                        if (! filled($data['birth_month'] ?? null)) {
                            return [];
                        }

                        return [
                            'ДР: ' . self::months()[$data['birth_month']],
                        ];
                    })
                    ->query(function ($query, array $data) {
                        return $query->when(
                            filled($data['birth_month'] ?? null),
                            fn ($query) => $query->where('birth_month', $data['birth_month']),
                        );
                    }),

                Filter::make('first_visit_filter')
                    ->form([
                        Placeholder::make('first_visit_filter_title')
                            ->hiddenLabel()
                            ->content('Фильтр по первому посещению'),

                        Select::make('month')
                            ->label('Месяц посещения')
                            ->placeholder('Выберите месяц')
                            ->options(self::months())
                            ->native(false),

                        Select::make('year')
                            ->label('Год посещения')
                            ->placeholder('Выберите год')
                            ->options(
                                collect(range(now()->year, 2020))
                                    ->mapWithKeys(fn ($year) => [$year => $year])
                                    ->toArray()
                            )
                            ->native(false),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['month'] ?? null)) {
                            $indicators[] = 'Месяц посещения: ' . self::months()[$data['month']];
                        }

                        if (filled($data['year'] ?? null)) {
                            $indicators[] = 'Год посещения: ' . $data['year'];
                        }

                        return $indicators;
                    })
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                filled($data['month'] ?? null),
                                fn ($query) => $query->whereMonth('first_visit_at', $data['month']),
                            )
                            ->when(
                                filled($data['year'] ?? null),
                                fn ($query) => $query->whereYear('first_visit_at', $data['year']),
                            );
                    }),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlayers::route('/'),
            'create' => CreatePlayer::route('/create'),
            'view' => ViewPlayer::route('/{record}'),
            'edit' => EditPlayer::route('/{record}/edit'),
        ];
    }
}