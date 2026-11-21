<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Evenings\EveningResource;
use App\Filament\Resources\Evenings\Schemas\EveningDetailsForm;
use App\Models\Evening;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class Schedule extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Расписание';

    protected static ?string $title = 'Расписание';

    protected static UnitEnum|string|null $navigationGroup = 'Клуб';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.schedule';

    public function getSubheading(): ?string
    {
        return 'Игры клуба. Время начала указано по Минску.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Добавить вечер')
                ->modalHeading('Новый вечер в расписании')
                ->model(Evening::class)
                ->schema(EveningDetailsForm::components(forSchedule: true))
                ->authorize(fn (): bool => EveningResource::canCreate())
                ->successNotificationTitle('Вечер добавлен в расписание'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Evening::query()->with(['project', 'eveningType']))
            ->defaultSort('played_at', 'asc')
            ->defaultGroup(Group::make('played_at')->date()->label('Дата'))
            ->columns([
                TextColumn::make('project.name')->label('Проект')->placeholder('Не указан')->searchable(),
                TextColumn::make('eveningType.name')->label('Тип вечера')->placeholder('Не указан')->searchable(),
                TextColumn::make('played_at')->label('Дата')->date('d.m.Y')->sortable(),
                TextColumn::make('start_time')->label('Время')
                    ->state(fn (Evening $record): string => $record->played_at->format('H:i')),
                TextColumn::make('address')->label('Адрес')->placeholder('Не указан')->wrap()->searchable(),
                TextColumn::make('price')->label('Стоимость')->numeric(decimalPlaces: 2)->suffix(' BYN')->placeholder('Не указана'),
            ])
            ->filters([
                Filter::make('period')
                    ->label('Период')
                    ->schema([
                        DatePicker::make('from')->label('С даты')->default(fn (): string => Evening::clubNow()->toDateString()),
                        DatePicker::make('until')->label('По дату'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('played_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('played_at', '<=', $date))),
                SelectFilter::make('project_id')->label('Проект')->relationship('project', 'name')->preload(),
                SelectFilter::make('evening_type_id')->label('Тип вечера')->relationship('eveningType', 'name')->preload(),
            ])
            ->recordActions([
                Action::make('open_evening')->label('Открыть вечер')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Evening $record): string => EveningResource::getUrl('view', ['record' => $record])),
                EditAction::make()->label('Изменить')
                    ->schema(EveningDetailsForm::components(forSchedule: true))
                    ->authorize(fn (Evening $record): bool => EveningResource::canEdit($record)),
                DeleteAction::make()->label('Удалить')
                    ->authorize(fn (Evening $record): bool => EveningResource::canDelete($record)),
            ])
            ->emptyStateHeading('В расписании пока нет вечеров')
            ->emptyStateDescription('Добавьте игру или выберите другой период.')
            ->defaultPaginationPageOption(25);
    }
}
