<?php

namespace App\Filament\Pages;

use App\Models\Evening;
use App\Models\FinancialCategory;
use App\Models\FinancialCategoryValue;
use App\Models\FinancialPeriodValue;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use UnitEnum;

class MonthlyFinances extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $slug = 'monthly-finances';

    protected static ?string $navigationLabel = 'Финансы по месяцам';

    protected static ?string $title = 'Финансы по месяцам';

    protected static UnitEnum | string | null $navigationGroup = 'Отчеты';

    protected static ?int $navigationSort = 15;

    protected string $view = 'filament.pages.monthly-finances';

    #[Url(as: 'from', history: true)]
    public string $periodFrom = '';

    #[Url(as: 'until', history: true)]
    public string $periodUntil = '';

    public string $pendingPeriodFrom = '';

    public string $pendingPeriodUntil = '';

    #[Url(as: 'sort', history: true)]
    public string $sortColumn = 'month';

    #[Url(as: 'dir', history: true)]
    public string $sortDirection = 'desc';

    #[Url(as: 'compare_a', history: true)]
    public string $comparisonMonthA = '';

    #[Url(as: 'compare_b', history: true)]
    public string $comparisonMonthB = '';

    /** @var array<int, array<string, mixed>> */
    public array $comparisonSummaryRows = [];

    /** @var array<int, array<string, mixed>> */
    public array $comparisonCategoryRows = [];

    public string $comparisonLabelA = '';

    public string $comparisonLabelB = '';

    /** @var array<int, array{month: string, label: string, revenue: float, expenses: float, profit: float, margin: float|null, revenue_change: float|null}> */
    public array $rows = [];

    public function mount(): void
    {
        $hasValidFrom = $this->isValidPeriod($this->periodFrom);
        $hasValidUntil = $this->isValidPeriod($this->periodUntil);
        $defaultEnd = $this->defaultEndMonth();

        if ($hasValidFrom && ! $hasValidUntil) {
            $from = Carbon::createFromFormat('!Y-m', $this->periodFrom);
            $this->periodUntil = ($from->gt($defaultEnd) ? $from : $defaultEnd)->format('Y-m');
        } elseif (! $hasValidFrom && $hasValidUntil) {
            $end = Carbon::createFromFormat('!Y-m', $this->periodUntil);
            $this->periodFrom = $end->copy()->subMonthsNoOverflow(11)->format('Y-m');
        } elseif (! $this->isValidRange($this->periodFrom, $this->periodUntil)) {
            $currentMonth = now()->startOfMonth();
            $this->periodFrom = $currentMonth->copy()->startOfYear()->format('Y-m');
            $this->periodUntil = $currentMonth->format('Y-m');
        }

        $this->pendingPeriodFrom = $this->periodFrom;
        $this->pendingPeriodUntil = $this->periodUntil;
        $this->refreshRows();

        if (! $this->isValidPeriod($this->comparisonMonthB)) {
            $this->comparisonMonthB = now()->format('Y-m');
        }

        if (! $this->isValidPeriod($this->comparisonMonthA)) {
            $this->comparisonMonthA = Carbon::createFromFormat('!Y-m', $this->comparisonMonthB)
                ->subMonthNoOverflow()
                ->format('Y-m');
        }

        $this->refreshComparison();
    }

    public function applyPeriodRange(): void
    {
        $this->resetErrorBag();

        if (! $this->isValidRange($this->pendingPeriodFrom, $this->pendingPeriodUntil)) {
            $this->addError('periodRange', 'Начальный месяц не может быть позже конечного.');

            return;
        }

        $this->periodFrom = $this->pendingPeriodFrom;
        $this->periodUntil = $this->pendingPeriodUntil;
        $this->refreshRows();
    }

    public function sortTable(string $column): void
    {
        if (! in_array($column, $this->sortableColumns(), true)) {
            return;
        }

        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'desc';
        }

        $this->sortRows();
    }

    public function applyComparison(): void
    {
        $this->resetErrorBag('comparisonRange');

        if (! $this->isValidPeriod($this->comparisonMonthA) || ! $this->isValidPeriod($this->comparisonMonthB)) {
            $this->addError('comparisonRange', 'Выберите два месяца для сравнения.');

            return;
        }

        $this->refreshComparison();
    }

    private function refreshComparison(): void
    {
        $categories = FinancialCategory::query()
            ->root()
            ->with('childrenRecursive')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $snapshotA = $this->financialSnapshot($this->comparisonMonthA, $categories);
        $snapshotB = $this->financialSnapshot($this->comparisonMonthB, $categories);

        $this->comparisonLabelA = $this->monthLabel($this->comparisonMonthA);
        $this->comparisonLabelB = $this->monthLabel($this->comparisonMonthB);
        $this->comparisonSummaryRows = [
            $this->comparisonRow('Общая выручка', $snapshotA['revenue'], $snapshotB['revenue']),
            $this->comparisonRow('Общие расходы', $snapshotA['expenses'], $snapshotB['expenses']),
            $this->comparisonRow('Чистая прибыль', $snapshotA['profit'], $snapshotB['profit']),
            [
                'label' => 'Маржа',
                'value_a' => $snapshotA['margin'],
                'value_b' => $snapshotB['margin'],
                'difference' => $snapshotA['margin'] === null || $snapshotB['margin'] === null
                    ? null
                    : round($snapshotB['margin'] - $snapshotA['margin'], 1),
                'change' => null,
                'type' => 'margin',
            ],
        ];

        $rows = [];
        $appendCategory = function (FinancialCategory $category, int $level, array $ancestorIds = []) use (&$appendCategory, &$rows, $snapshotA, $snapshotB): void {
            $rows[] = [
                ...$this->comparisonRow(
                    $category->name,
                    $snapshotA['category_totals'][$category->id] ?? 0.0,
                    $snapshotB['category_totals'][$category->id] ?? 0.0,
                ),
                'id' => $category->id,
                'level' => $level,
                'is_parent' => $category->childrenRecursive->isNotEmpty(),
                'ancestor_ids' => $ancestorIds,
            ];

            foreach ($category->childrenRecursive as $child) {
                $appendCategory($child, $level + 1, [...$ancestorIds, $category->id]);
            }
        };

        foreach ($categories as $category) {
            $appendCategory($category, 1);
        }

        $this->comparisonCategoryRows = $rows;
    }

    private function financialSnapshot(string $period, $categories): array
    {
        $start = Carbon::createFromFormat('!Y-m', $period)->startOfMonth();
        $end = $start->copy()->addMonth();
        $evenings = Evening::query()
            ->where('played_at', '>=', $start)
            ->where('played_at', '<', $end)
            ->withSum('participants', 'paid_amount')
            ->withSum('staff', 'salary')
            ->withSum('expenses', 'amount')
            ->get();
        $clubRevenue = (float) $evenings->sum('participants_sum_paid_amount');
        $corporateRevenue = (float) (FinancialPeriodValue::query()
            ->whereDate('period', $start->toDateString())
            ->value('corporate_revenue') ?? 0);
        $savedValues = FinancialCategoryValue::query()
            ->whereDate('period', $start->toDateString())
            ->pluck('amount', 'financial_category_id');
        $eventSalaries = (float) $evenings->sum('staff_sum_salary');
        $eventOtherExpenses = (float) $evenings->sum(
            fn (Evening $evening): float => (float) $evening->expenses_sum_amount + (float) $evening->other_expenses,
        );
        $categoryTotals = [];

        $calculateCategory = function (FinancialCategory $category) use (&$calculateCategory, &$categoryTotals, $savedValues, $eventSalaries, $eventOtherExpenses): float {
            if ($category->childrenRecursive->isEmpty()) {
                $total = match ($category->code) {
                    'team_event_salaries' => $eventSalaries,
                    'team_event_other_expenses' => $eventOtherExpenses,
                    default => (float) ($savedValues[$category->id] ?? 0),
                };
            } else {
                $total = (float) $category->childrenRecursive->sum(
                    fn (FinancialCategory $child): float => $calculateCategory($child),
                );
            }

            return $categoryTotals[$category->id] = $total;
        };

        $expenses = (float) $categories->sum(
            fn (FinancialCategory $category): float => $calculateCategory($category),
        );
        $revenue = $clubRevenue + $corporateRevenue;
        $profit = $revenue - $expenses;

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'profit' => $profit,
            'margin' => $revenue == 0.0 ? null : round(($profit / $revenue) * 100, 1),
            'category_totals' => $categoryTotals,
        ];
    }

    private function comparisonRow(string $label, float $valueA, float $valueB): array
    {
        return [
            'label' => $label,
            'value_a' => $valueA,
            'value_b' => $valueB,
            'difference' => $valueB - $valueA,
            'change' => $valueA == 0.0 ? null : round((($valueB - $valueA) / abs($valueA)) * 100, 1),
            'type' => 'money',
        ];
    }

    private function monthLabel(string $period): string
    {
        return Str::ucfirst(Carbon::createFromFormat('!Y-m', $period)->locale('ru')->translatedFormat('F Y'));
    }

    private function refreshRows(): void
    {
        $from = Carbon::createFromFormat('!Y-m', $this->periodFrom)->startOfMonth();
        $until = Carbon::createFromFormat('!Y-m', $this->periodUntil)->endOfMonth();
        $comparisonFrom = $from->copy()->subMonthNoOverflow()->startOfMonth();

        $leafCategories = FinancialCategory::query()
            ->whereDoesntHave('children')
            ->get(['id', 'code']);
        $manualCategoryIds = $leafCategories
            ->whereNotIn('code', [
                'team_event_salaries',
                'team_event_other_expenses',
            ])
            ->pluck('id');

        $manualExpensesByMonth = FinancialCategoryValue::query()
            ->whereIn('financial_category_id', $manualCategoryIds)
            ->whereBetween('period', [$comparisonFrom->toDateString(), $until->toDateString()])
            ->get(['period', 'amount'])
            ->groupBy(fn (FinancialCategoryValue $value): string => $value->period->format('Y-m'))
            ->map(fn ($values): float => (float) $values->sum('amount'));

        $corporateRevenueByMonth = FinancialPeriodValue::query()
            ->whereBetween('period', [$comparisonFrom->toDateString(), $until->toDateString()])
            ->get(['period', 'corporate_revenue'])
            ->keyBy(fn (FinancialPeriodValue $value): string => $value->period->format('Y-m'));

        $totalsByMonth = Evening::query()
            ->whereBetween('played_at', [$comparisonFrom, $until])
            ->withSum('participants', 'paid_amount')
            ->withSum('staff', 'salary')
            ->withSum('expenses', 'amount')
            ->get()
            ->groupBy(fn (Evening $evening): string => $evening->played_at->format('Y-m'))
            ->map(function ($evenings, string $month) use ($corporateRevenueByMonth, $manualExpensesByMonth): array {
                $clubRevenue = (float) $evenings->sum('participants_sum_paid_amount');
                $corporateRevenue = (float) ($corporateRevenueByMonth->get($month)?->corporate_revenue ?? 0);
                $teamEventExpenses = (float) $evenings->sum(
                    fn (Evening $evening): float => (float) $evening->staff_sum_salary
                        + (float) $evening->expenses_sum_amount
                        + (float) $evening->other_expenses,
                );
                $revenue = $clubRevenue + $corporateRevenue;
                $expenses = $teamEventExpenses + (float) $manualExpensesByMonth->get($month, 0);

                return [
                    'revenue' => $revenue,
                    'expenses' => $expenses,
                    'profit' => $revenue - $expenses,
                ];
            });

        foreach ($corporateRevenueByMonth as $month => $periodValue) {
            if ($totalsByMonth->has($month)) {
                continue;
            }

            $revenue = (float) $periodValue->corporate_revenue;
            $expenses = (float) $manualExpensesByMonth->get($month, 0);
            $totalsByMonth->put($month, [
                'revenue' => $revenue,
                'expenses' => $expenses,
                'profit' => $revenue - $expenses,
            ]);
        }

        foreach ($manualExpensesByMonth as $month => $expenses) {
            if ($totalsByMonth->has($month)) {
                continue;
            }

            $totalsByMonth->put($month, [
                'revenue' => 0.0,
                'expenses' => (float) $expenses,
                'profit' => -(float) $expenses,
            ]);
        }

        $rows = [];

        for ($month = $from->copy(); $month->lte($until); $month->addMonth()) {
            $key = $month->format('Y-m');
            $previousKey = $month->copy()->subMonthNoOverflow()->format('Y-m');
            $totals = $totalsByMonth->get($key, [
                'revenue' => 0.0,
                'expenses' => 0.0,
                'profit' => 0.0,
            ]);
            $previousRevenue = (float) ($totalsByMonth->get($previousKey)['revenue'] ?? 0);
            $revenue = (float) $totals['revenue'];
            $profit = (float) $totals['profit'];

            $rows[] = [
                'month' => $key,
                'label' => Str::ucfirst($month->copy()->locale('ru')->translatedFormat('F Y')),
                'revenue' => $revenue,
                'expenses' => (float) $totals['expenses'],
                'profit' => $profit,
                'margin' => $revenue == 0.0 ? null : round(($profit / $revenue) * 100, 1),
                'revenue_change' => $previousRevenue == 0.0
                    ? null
                    : round((($revenue - $previousRevenue) / abs($previousRevenue)) * 100, 1),
            ];
        }

        $this->rows = $rows;
        $this->sortRows();
    }

    private function sortRows(): void
    {
        if (! in_array($this->sortColumn, $this->sortableColumns(), true)) {
            $this->sortColumn = 'month';
        }

        if (! in_array($this->sortDirection, ['asc', 'desc'], true)) {
            $this->sortDirection = 'desc';
        }

        usort($this->rows, function (array $left, array $right): int {
            $leftValue = $left[$this->sortColumn];
            $rightValue = $right[$this->sortColumn];

            if ($leftValue === null || $rightValue === null) {
                if ($leftValue === $rightValue) {
                    return $right['month'] <=> $left['month'];
                }

                return $leftValue === null ? 1 : -1;
            }

            $comparison = $leftValue <=> $rightValue;

            if ($comparison === 0) {
                return $right['month'] <=> $left['month'];
            }

            return $this->sortDirection === 'asc' ? $comparison : -$comparison;
        });
    }

    /** @return array<int, string> */
    private function sortableColumns(): array
    {
        return ['month', 'revenue', 'expenses', 'profit', 'margin', 'revenue_change'];
    }

    private function defaultEndMonth(): Carbon
    {
        $currentMonth = now()->startOfMonth();
        $latestDate = Evening::query()
            ->where('played_at', '<', $currentMonth->copy()->addMonth())
            ->max('played_at');

        return $latestDate
            ? Carbon::parse($latestDate)->startOfMonth()
            : $currentMonth;
    }

    private function isValidRange(string $from, string $until): bool
    {
        if (! $this->isValidPeriod($from) || ! $this->isValidPeriod($until)) {
            return false;
        }

        return Carbon::createFromFormat('!Y-m', $from)
            ->lte(Carbon::createFromFormat('!Y-m', $until));
    }

    private function isValidPeriod(string $period): bool
    {
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period) === 1;
    }
}
