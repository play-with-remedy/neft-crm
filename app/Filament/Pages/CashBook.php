<?php

namespace App\Filament\Pages;

use App\Models\Evening;
use App\Models\PaymentType;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use BackedEnum;
use App\Filament\Resources\Evenings\EveningResource;
use Illuminate\Support\Str;

class CashBook extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Кассовая книга';

    protected static ?string $title = 'Кассовая книга';

    protected static UnitEnum|string|null $navigationGroup = 'Отчеты';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.cash-book';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Evening::query()
                    ->with([
                        'eveningType',
                        'project',
                        'participants.paymentType',
                        'staff.host',
                        'expenses.category',
                    ])
            )
            ->defaultSort('played_at', 'desc')
            ->columns([
                TextColumn::make('row_number')
                    ->label('№')
                    ->rowIndex(),

                TextColumn::make('played_at')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('day')
                    ->label('День')
                    ->state(
                        fn (Evening $record): string => Str::ucfirst(
                            $record->played_at
                                ?->copy()
                                ->locale('ru')
                                ->translatedFormat('l') ?? ''
                        )
                    ),

                TextColumn::make('project.name')
                    ->label('Проект')
                    ->searchable(),

                TextColumn::make('eveningType.name')
                    ->label('Тип')
                    ->searchable(),

                TextColumn::make('revenue')
                    ->label('Выручка')
                    ->state(fn (Evening $record): int => (int) $record->participants->sum('paid_amount')),

                TextColumn::make('profit')
                    ->label('Прибыль')
                    ->state(function (Evening $record): int {
                        $revenue = (int) $record->participants->sum('paid_amount');
                        $expenses = (int) $record->expenses->sum('amount');
                        $staffSalary = (int) $record->staff->sum('salary');
                        $otherExpenses = (int) $record->other_expenses;

                        return $revenue - $expenses - $staffSalary - $otherExpenses;
                    }),

                TextColumn::make('expenses')
                    ->label('Расходы')
                    ->state(function (Evening $record): int {
                        return (int) $record->expenses->sum('amount')
                            + (int) $record->other_expenses;
                    }),

                ...$this->paymentTypeColumns(),
            ])
            ->recordUrl(
                fn (Evening $record): string => EveningResource::getUrl('view', ['record' => $record])
            )
            ->filters([
                SelectFilter::make('project_id')
                    ->label('Проект')
                    ->placeholder('Все проекты')
                    ->relationship('project', 'name'),

                SelectFilter::make('evening_type_id')
                    ->label('Тип')
                    ->placeholder('Все типы')
                    ->relationship('eveningType', 'name'),


                Filter::make('played_at')
                    ->label('Период')
                    ->form([
                        DatePicker::make('from')
                            ->label('С даты'),

                        DatePicker::make('until')
                            ->label('По дату'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date) => $query->whereDate('played_at', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date) => $query->whereDate('played_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'С даты: ' . Carbon::parse($data['from'])->format('d.m.Y');
                        }

                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'По дату: ' . Carbon::parse($data['until'])->format('d.m.Y');
                        }

                        return $indicators;
                    }),
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
            });
    }

    public function getTotals(): array
    {
        $records = $this->getFilteredTableQuery()
            ->with([
                'participants.paymentType',
                'staff.host',
                'expenses.category',
            ])
            ->get();

        $revenue = 0;
        $paymentTypes = PaymentType::query()->orderBy('id')->get();
        $paymentTypeTotals = $paymentTypes
            ->mapWithKeys(fn (PaymentType $paymentType): array => [
                $paymentType->id => [
                    'label' => $paymentType->type,
                    'value' => 0,
                ],
            ])
            ->all();
        $prizeFund = 0;
        $expenses = 0;
        $staffSalary = 0;

        foreach ($records as $evening) {
            $revenue += (int) $evening->participants->sum('paid_amount');

            foreach ($evening->participants as $participant) {
                $paymentTypeId = (int) $participant->payment_type_id;

                if (isset($paymentTypeTotals[$paymentTypeId])) {
                    $paymentTypeTotals[$paymentTypeId]['value'] += (int) $participant->paid_amount;
                }
            }

            $prizeFund += (int) $evening->expenses
                ->filter(fn ($expense) => $expense->category?->name === 'Призовые')
                ->sum('amount');

            $expenses += (int) $evening->expenses->sum('amount')
                + (int) $evening->other_expenses;

            $staffSalary += (int) $evening->staff->sum('salary');
        }

        return [
            'revenue' => $revenue,
            'payment_types' => array_values($paymentTypeTotals),
            'prize_fund' => $prizeFund,
            'expenses' => $expenses,
            'staff_salary' => $staffSalary,
            'profit' => $revenue - $expenses - $staffSalary,
        ];
    }

    private function paymentTypeColumns(): array
    {
        return PaymentType::query()
            ->orderBy('id')
            ->get()
            ->map(function (PaymentType $paymentType): TextColumn {
                $paymentTypeId = (int) $paymentType->id;

                return TextColumn::make("payment_type_{$paymentTypeId}")
                    ->label($paymentType->type)
                    ->state(fn (Evening $record): int => (int) $record->participants
                        ->where('payment_type_id', $paymentTypeId)
                        ->sum('paid_amount'))
                    ->alignCenter();
            })
            ->all();
    }
}
