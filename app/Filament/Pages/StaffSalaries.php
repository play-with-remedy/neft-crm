<?php

namespace App\Filament\Pages;

use App\Models\Evening;
use App\Models\EveningStaff;
use App\Models\Host;
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

class StaffSalaries extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Зарплаты сотрудников';

    protected static ?string $title = 'Зарплаты сотрудников';

    protected static UnitEnum | string | null $navigationGroup = 'Отчеты';

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

                $this->salaryColumn('host_salary', 'host_evenings_count', 'Ведущий'),
                $this->salaryColumn('admin_salary', 'admin_evenings_count', 'Админ'),
                $this->salaryColumn('manager_salary', 'manager_evenings_count', 'Менеджер'),
                $this->salaryColumn('supervisor_salary', 'supervisor_evenings_count', 'Супервайзер'),

                $this->salaryColumn('total_salary', 'total_evenings_count', 'Всего')
                    ->weight('bold'),
            ])
            ->filters([
                SelectFilter::make('month')
                    ->label('По месяцу')
                    ->placeholder('Все месяцы')
                    ->options(fn (): array => $this->monthOptions())
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

    private function salaryColumn(string $name, string $countName, string $label): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->formatStateUsing(
                fn ($state): string => number_format((int) $state, 0, ',', ' ') . ' BYN'
            )
            ->description(
                function (Host $record) use ($countName): string {
                    $count = (int) $record->getAttribute($countName);

                    return number_format($count, 0, ',', ' ')
                        . ' '
                        . $this->eveningWord($count);
                }
            )
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
        return Host::query()->addSelect([
            'host_salary' => $this->salarySubquery('host'),
            'host_evenings_count' => $this->eveningsCountSubquery('host'),
            'admin_salary' => $this->salarySubquery('admin'),
            'admin_evenings_count' => $this->eveningsCountSubquery('admin'),
            'manager_salary' => $this->salarySubquery('manager'),
            'manager_evenings_count' => $this->eveningsCountSubquery('manager'),
            'supervisor_salary' => $this->salarySubquery('supervisor'),
            'supervisor_evenings_count' => $this->eveningsCountSubquery('supervisor'),
            'total_salary' => $this->salarySubquery(),
            'total_evenings_count' => $this->eveningsCountSubquery(),
        ]);
    }

    private function salarySubquery(?string $role = null): Builder
    {
        [$periodStart, $periodEnd] = $this->activePeriod();

        return EveningStaff::query()
            ->selectRaw('COALESCE(SUM(evening_staff.salary), 0)')
            ->join('evenings', 'evenings.id', '=', 'evening_staff.evening_id')
            ->whereColumn('evening_staff.host_id', 'hosts.id')
            ->when($role, fn (Builder $query): Builder => $query->where('evening_staff.role', $role))
            ->when($periodStart, fn (Builder $query): Builder => $query
                ->where('evenings.played_at', '>=', $periodStart)
                ->where('evenings.played_at', '<', $periodEnd));
    }

    private function eveningsCountSubquery(?string $role = null): Builder
    {
        [$periodStart, $periodEnd] = $this->activePeriod();

        return EveningStaff::query()
            ->selectRaw('COUNT(DISTINCT evening_staff.evening_id)')
            ->join('evenings', 'evenings.id', '=', 'evening_staff.evening_id')
            ->whereColumn('evening_staff.host_id', 'hosts.id')
            ->when($role, fn (Builder $query): Builder => $query->where('evening_staff.role', $role))
            ->when($periodStart, fn (Builder $query): Builder => $query
                ->where('evenings.played_at', '>=', $periodStart)
                ->where('evenings.played_at', '<', $periodEnd));
    }

    private function activePeriod(): array
    {
        $month = $this->getTableFilterState('month')['value'] ?? null;
        $periodStart = filled($month)
            ? Carbon::createFromFormat('!Y-m', $month)->startOfMonth()
            : null;

        return [$periodStart, $periodStart?->copy()->addMonth()];
    }

    private function monthOptions(): array
    {
        return Evening::query()
            ->whereHas('staff')
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
