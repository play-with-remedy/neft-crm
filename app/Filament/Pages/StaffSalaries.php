<?php

namespace App\Filament\Pages;

use App\Models\EveningStaff;
use App\Models\EveningType;
use App\Models\Host;
use App\Models\Project;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class StaffSalaries extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Зарплаты сотрудников';

    protected static ?string $title = 'Зарплаты сотрудников';

    protected static UnitEnum|string|null $navigationGroup = 'Отчеты';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.pages.staff-salaries';

    public function getSubheading(): ?string
    {
        return 'Выплаты сотрудникам с разбивкой по ролям';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getStaffQuery())
            ->columns([
                TextColumn::make('position')
                    ->label('№')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('nickname')
                    ->label('Сотрудник')
                    ->searchable()
                    ->sortable(),

                $this->salaryColumn('host_salary', 'host_evenings_count', 'Ведущий', 'host'),
                $this->salaryColumn('admin_salary', 'admin_evenings_count', 'Админ', 'admin'),
                $this->salaryColumn('manager_salary', 'manager_evenings_count', 'Менеджер', 'manager'),
                $this->salaryColumn('supervisor_salary', 'supervisor_evenings_count', 'Супервайзер', 'supervisor'),

                $this->salaryColumn('total_salary', 'total_evenings_count', 'Всего', null)
                    ->weight('bold'),
            ])
            ->filters([
                Filter::make('played_at')
                    ->label('Период проведения')
                    ->form([
                        DatePicker::make('from')
                            ->label('С даты'),
                        DatePicker::make('until')
                            ->label('По дату'),
                    ])
                    ->query(fn (Builder $query): Builder => $query)
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'С даты: '.Carbon::parse($data['from'])->format('d.m.Y');
                        }

                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'По дату: '.Carbon::parse($data['until'])->format('d.m.Y');
                        }

                        return $indicators;
                    }),

                SelectFilter::make('evening_type_id')
                    ->label('Тип проекта')
                    ->placeholder('Все типы проектов')
                    ->options(fn (): array => EveningType::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->preload()
                    ->query(fn (Builder $query): Builder => $query),

                SelectFilter::make('project_id')
                    ->label('Проект')
                    ->placeholder('Все проекты')
                    ->options(fn (): array => Project::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->preload()
                    ->query(fn (Builder $query): Builder => $query),
            ])
            ->filtersApplyAction(
                fn (Action $action): Action => $action->label('Применить')
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
            ->defaultSort('total_salary', 'desc')
            ->emptyStateHeading('Сотрудники не найдены')
            ->emptyStateIcon('heroicon-o-user-group')
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([10, 25, 50, 100]);
    }

    private function salaryColumn(string $name, string $countName, string $label, ?string $role): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->formatStateUsing(
                fn ($state): string => number_format((int) $state, 0, ',', ' ').' BYN'
            )
            ->description(
                function (Host $record) use ($countName): string {
                    $count = (int) $record->getAttribute($countName);

                    return number_format($count, 0, ',', ' ')
                        .' '
                        .$this->eveningWord($count);
                }
            )
            ->action(
                Action::make("view_{$name}_evenings")
                    ->action(fn (): null => null)
                    ->modalHeading(fn (Host $record): string => "{$record->nickname} — {$label}")
                    ->modalContent(fn (Host $record): View => view(
                        'filament.pages.partials.staff-salary-evenings',
                        [
                            'hostId' => $record->getKey(),
                            'role' => $role,
                            'periodFrom' => $this->activePeriod()[0]?->toDateString(),
                            'periodUntil' => $this->activePeriod()[1]?->toDateString(),
                            'periodLabel' => $this->activePeriodLabel(),
                            'eveningTypeId' => $this->activeEveningTypeId(),
                            'projectId' => $this->activeProjectId(),
                        ],
                    ))
                    ->modalWidth(Width::FourExtraLarge)
                    ->stickyModalHeader()
                    ->stickyModalFooter()
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Закрыть'),
            )
            ->tooltip('Нажмите, чтобы посмотреть вечера')
            ->sortable()
            ->alignCenter();
    }

    private function eveningWord(int $count): string
    {
        if (($count % 10 === 1) && ($count % 100 !== 11)) {
            return 'вечер';
        }

        if (
            in_array($count % 10, [2, 3, 4], true)
            && ! in_array($count % 100, [12, 13, 14], true)
        ) {
            return 'вечера';
        }

        return 'вечеров';
    }

    private function getStaffQuery(): Builder
    {
        return Host::query()
            ->leftJoinSub($this->staffStatsSubquery(), 'staff_stats', fn ($join) => $join
                ->on('staff_stats.host_id', '=', 'hosts.id'))
            ->select('hosts.*')
            ->selectRaw('COALESCE(staff_stats.host_salary, 0) AS host_salary')
            ->selectRaw('COALESCE(staff_stats.host_evenings_count, 0) AS host_evenings_count')
            ->selectRaw('COALESCE(staff_stats.admin_salary, 0) AS admin_salary')
            ->selectRaw('COALESCE(staff_stats.admin_evenings_count, 0) AS admin_evenings_count')
            ->selectRaw('COALESCE(staff_stats.manager_salary, 0) AS manager_salary')
            ->selectRaw('COALESCE(staff_stats.manager_evenings_count, 0) AS manager_evenings_count')
            ->selectRaw('COALESCE(staff_stats.supervisor_salary, 0) AS supervisor_salary')
            ->selectRaw('COALESCE(staff_stats.supervisor_evenings_count, 0) AS supervisor_evenings_count')
            ->selectRaw('COALESCE(staff_stats.total_salary, 0) AS total_salary')
            ->selectRaw('COALESCE(staff_stats.total_evenings_count, 0) AS total_evenings_count');
    }

    private function staffStatsSubquery(): Builder
    {
        [$periodFrom, $periodUntil] = $this->activePeriod();

        return EveningStaff::query()
            ->join('evenings', 'evenings.id', '=', 'evening_staff.evening_id')
            ->select('evening_staff.host_id')
            ->selectRaw("SUM(CASE WHEN evening_staff.role = 'host' THEN evening_staff.salary ELSE 0 END) AS host_salary")
            ->selectRaw("COUNT(DISTINCT CASE WHEN evening_staff.role = 'host' THEN evening_staff.evening_id END) AS host_evenings_count")
            ->selectRaw("SUM(CASE WHEN evening_staff.role = 'admin' THEN evening_staff.salary ELSE 0 END) AS admin_salary")
            ->selectRaw("COUNT(DISTINCT CASE WHEN evening_staff.role = 'admin' THEN evening_staff.evening_id END) AS admin_evenings_count")
            ->selectRaw("SUM(CASE WHEN evening_staff.role = 'manager' THEN evening_staff.salary ELSE 0 END) AS manager_salary")
            ->selectRaw("COUNT(DISTINCT CASE WHEN evening_staff.role = 'manager' THEN evening_staff.evening_id END) AS manager_evenings_count")
            ->selectRaw("SUM(CASE WHEN evening_staff.role = 'supervisor' THEN evening_staff.salary ELSE 0 END) AS supervisor_salary")
            ->selectRaw("COUNT(DISTINCT CASE WHEN evening_staff.role = 'supervisor' THEN evening_staff.evening_id END) AS supervisor_evenings_count")
            ->selectRaw('SUM(evening_staff.salary) AS total_salary')
            ->selectRaw('COUNT(DISTINCT evening_staff.evening_id) AS total_evenings_count')
            ->when($periodFrom, fn (Builder $query, Carbon $date): Builder => $query
                ->where('evenings.played_at', '>=', $date))
            ->when($periodUntil, fn (Builder $query, Carbon $date): Builder => $query
                ->where('evenings.played_at', '<', $date))
            ->when($this->activeEveningTypeId(), fn (Builder $query, int $id): Builder => $query
                ->where('evenings.evening_type_id', $id))
            ->when($this->activeProjectId(), fn (Builder $query, int $id): Builder => $query
                ->where('evenings.project_id', $id))
            ->groupBy('evening_staff.host_id');
    }

    private function activeEveningTypeId(): ?int
    {
        $id = $this->getTableFilterState('evening_type_id')['value'] ?? null;

        return filled($id) ? (int) $id : null;
    }

    private function activeProjectId(): ?int
    {
        $id = $this->getTableFilterState('project_id')['value'] ?? null;

        return filled($id) ? (int) $id : null;
    }

    private function activePeriod(): array
    {
        $period = $this->getTableFilterState('played_at') ?? [];

        return [
            filled($period['from'] ?? null) ? Carbon::parse($period['from'])->startOfDay() : null,
            filled($period['until'] ?? null) ? Carbon::parse($period['until'])->addDay()->startOfDay() : null,
        ];
    }

    private function activePeriodLabel(): string
    {
        $period = $this->getTableFilterState('played_at') ?? [];
        $from = filled($period['from'] ?? null)
            ? Carbon::parse($period['from'])->format('d.m.Y')
            : null;
        $until = filled($period['until'] ?? null)
            ? Carbon::parse($period['until'])->format('d.m.Y')
            : null;

        return match (true) {
            $from !== null && $until !== null => "{$from} — {$until}",
            $from !== null => "с {$from}",
            $until !== null => "по {$until}",
            default => 'За всё время',
        };
    }
}
