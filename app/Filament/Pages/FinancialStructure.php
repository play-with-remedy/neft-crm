<?php

namespace App\Filament\Pages;

use App\Models\FinancialCategory;
use App\Models\FinancialPeriodValue;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use App\Models\FinancialCategoryValue;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialStructure extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected static ?string $slug = 'monthly-expenses';

    protected static ?string $navigationLabel = 'Месячные расходы';

    protected static UnitEnum|string|null $navigationGroup = 'Отчеты';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Месячные расходы';

    protected string $view = 'filament.pages.financial-structure';

    /**
     * Выбранный месяц в формате YYYY-MM.
     */
    public string $period;

    /**
     * Включён ли режим редактирования.
     */
    public bool $isEditing = false;

    /**
     * Дерево финансовых статей.
     */
    public Collection $categories;

    /**
     * Значения конечных статей.
     *
     * [
     *     categoryId => [
     *         'amount' => '',
     *         'details' => '',
     *     ],
     * ]
     */
    public array $values = [];

    public int|float|string|null $corporateRevenue = '';

    public float $teamEventSalaries = 0;

    public float $teamEventOtherExpenses = 0;

    public function mount(): void
    {
        $this->period = now()->format('Y-m');

        $this->loadCategories();
        $this->loadValues();
    }

    /**
     * Загружает дерево статей.
     */
    public function loadCategories(): void
    {
        $this->categories = FinancialCategory::query()
            ->root()
            ->with('childrenRecursive')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * При смене месяца выходим из режима редактирования
     * и загружаем значения нового месяца.
     */
    public function updatedPeriod(): void
    {
        $this->isEditing = false;

        $this->resetValidation();

        $this->loadValues();
    }

    /**
     * Включает режим редактирования.
     */
    public function startEditing(): void
    {
        $this->resetValidation();

        /*
         * Повторно загружаем значения из базы,
         * чтобы редактировать актуальные данные.
         */
        $this->loadValues();

        $this->isEditing = true;
    }

    /**
     * Отменяет редактирование.
     */
    public function cancelEditing(): void
    {
        $this->resetValidation();

        /*
         * Возвращаем значения из базы,
         * отменяя несохранённые изменения.
         */
        $this->loadValues();

        $this->isEditing = false;
    }

    /**
     * Загружает сохранённые значения выбранного месяца.
     */
    public function loadValues(): void
    {
        $period = $this->normalizedPeriod();

        $savedValues = FinancialCategoryValue::query()
            ->whereDate('period', $period)
            ->get()
            ->keyBy('financial_category_id');

        $periodValues = FinancialPeriodValue::query()
            ->whereDate('period', $period)
            ->first();

        $this->corporateRevenue =
            $periodValues?->corporate_revenue ?? '';

        $this->teamEventSalaries =
            $this->calculateTeamEventSalaries();

        $this->teamEventOtherExpenses =
            $this->calculateTeamEventOtherExpenses();

        $this->values = [];

        foreach ($this->leafCategories() as $category) {
            if (in_array($category->code, [
                'team_event_salaries',
                'team_event_other_expenses',
            ], true)) {
                continue;
            }

            $savedValue = $savedValues->get($category->id);

            $this->values[$category->id] = [
                'amount' => $savedValue?->amount ?? '',
                'details' => $savedValue?->details ?? '',
            ];
        }
    }

    /**
     * Сохраняет значения выбранного месяца.
     */
    public function save(): void
    {
        if (! $this->isEditing) {
            return;
        }

        $this->validate([
            'period' => [
                'required',
                'date_format:Y-m',
            ],

            'values' => [
                'array',
            ],

            'values.*.amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'values.*.details' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'corporateRevenue' => [
                'nullable',
                'numeric',
                'min:0',
            ],

        ], [
            'period.required' =>
                'Выберите месяц.',

            'period.date_format' =>
                'Выбран неправильный формат месяца.',

            'values.*.amount.numeric' =>
                'Сумма должна быть числом.',

            'values.*.amount.min' =>
                'Сумма не может быть отрицательной.',

            'values.*.details.max' =>
                'Поле деталей не должно превышать 5000 символов.',

            'corporateRevenue.numeric' =>
                'Выручка корпоративов должна быть числом.',

            'corporateRevenue.min' =>
                'Выручка корпоративов не может быть отрицательной.',

        ]);

        $period = $this->normalizedPeriod();

        /*
         * Разрешаем сохранять значения только
         * для конечных статей.
         */
        $leafCategories = $this->leafCategories()->keyBy('id');

        DB::transaction(function () use (
            $period,
            $leafCategories
        ): void {
            FinancialPeriodValue::query()
                ->updateOrCreate(
                    ['period' => $period],
                    [
                        'corporate_revenue' =>
                            $this->corporateRevenue === ''
                            || $this->corporateRevenue === null
                                ? 0
                                : $this->corporateRevenue,

                    ],
                );

            foreach ($this->values as $categoryId => $data) {
                $categoryId = (int) $categoryId;

                if (! $leafCategories->has($categoryId)) {
                    continue;
                }

                $amount = $data['amount'] ?? null;
                $details = trim((string) ($data['details'] ?? ''));

                $amountIsEmpty =
                    $amount === null
                    || $amount === '';

                /*
                 * Если сумма и детали очищены,
                 * удаляем значение этого месяца.
                 */
                if ($amountIsEmpty && $details === '') {
                    FinancialCategoryValue::query()
                        ->where(
                            'financial_category_id',
                            $categoryId
                        )
                        ->whereDate('period', $period)
                        ->delete();

                    continue;
                }

                FinancialCategoryValue::query()
                    ->updateOrCreate(
                        [
                            'financial_category_id' =>
                                $categoryId,

                            'period' => $period,
                        ],
                        [
                            'amount' => $amountIsEmpty
                                ? 0
                                : $amount,

                            'details' => $details === ''
                                ? null
                                : $details,
                        ],
                    );
            }
        });

        $this->loadValues();

        $this->isEditing = false;

        Notification::make()
            ->title('Данные сохранены')
            ->body(
                'Значения за '
                . $this->formattedPeriod()
                . ' успешно сохранены.'
            )
            ->success()
            ->send();
    }

    /**
     * Возвращает сумму конкретной категории.
     *
     * Для конечной статьи берём её значение.
     * Для родителя суммируем всех детей.
     */
    public function categoryTotal(
        FinancialCategory $category
    ): float {
        if ($category->childrenRecursive->isEmpty()) {
            if ($category->code === 'team_event_salaries') {
                return $this->teamEventSalaries;
            }

            if ($category->code === 'team_event_other_expenses') {
                return $this->teamEventOtherExpenses;
            }

            return (float) (
                $this->values[$category->id]['amount'] ?? 0
            );
        }

        return (float) $category
            ->childrenRecursive
            ->sum(
                fn (FinancialCategory $child): float =>
                    $this->categoryTotal($child)
            );
    }

    private function calculateTeamEventSalaries(): float
    {
        $periodStart = Carbon::createFromFormat(
            'Y-m',
            $this->period
        )->startOfMonth();

        $periodEnd = $periodStart
            ->copy()
            ->addMonth();

        return (float) DB::table('evening_staff')
            ->join(
                'evenings',
                'evenings.id',
                '=',
                'evening_staff.evening_id'
            )
            ->where(
                'evenings.played_at',
                '>=',
                $periodStart
            )
            ->where(
                'evenings.played_at',
                '<',
                $periodEnd
            )
            ->sum('evening_staff.salary');
    }

    private function calculateTeamEventOtherExpenses(): float
    {
        $periodStart = Carbon::createFromFormat(
            'Y-m',
            $this->period
        )->startOfMonth();

        $periodEnd = $periodStart
            ->copy()
            ->addMonth();

        $categorizedExpenses = (float) DB::table('evening_expenses')
            ->join(
                'evenings',
                'evenings.id',
                '=',
                'evening_expenses.evening_id'
            )
            ->where(
                'evenings.played_at',
                '>=',
                $periodStart
            )
            ->where(
                'evenings.played_at',
                '<',
                $periodEnd
            )
            ->sum('evening_expenses.amount');

        $otherExpenses = (float) DB::table('evenings')
            ->where('played_at', '>=', $periodStart)
            ->where('played_at', '<', $periodEnd)
            ->sum('other_expenses');

        return $categorizedExpenses + $otherExpenses;
    }

    /**
     * Выручка клуба за выбранный месяц.
     */
    public function clubRevenue(): float
    {
        $periodStart = Carbon::createFromFormat(
            'Y-m',
            $this->period
        )->startOfMonth();

        $periodEnd = $periodStart
            ->copy()
            ->addMonth();

        return (float) DB::table('evening_participants')
            ->join(
                'evenings',
                'evenings.id',
                '=',
                'evening_participants.evening_id'
            )
            ->where(
                'evenings.played_at',
                '>=',
                $periodStart
            )
            ->where(
                'evenings.played_at',
                '<',
                $periodEnd
            )
            ->sum('evening_participants.paid_amount');
    }

    /**
     * Форматирует сумму для отображения.
     */
    public function formatAmount(
        int|float|string|null $amount
    ): string {
        return number_format(
            (float) $amount,
            2,
            ',',
            ' '
        );
    }

    public function formatPercentage(
        int|float|string|null $amount,
        int|float|string|null $total
    ): string {
        $total = (float) $total;

        if ($total <= 0) {
            return '0,00 %';
        }

        return number_format(
            ((float) $amount / $total) * 100,
            2,
            ',',
            ' '
        ) . ' %';
    }

    /**
     * Возвращает месяц в виде первого числа месяца.
     */
    private function normalizedPeriod(): string
    {
        return Carbon::createFromFormat(
            'Y-m',
            $this->period
        )
            ->startOfMonth()
            ->toDateString();
    }

    /**
     * Человекочитаемое название периода.
     */
    private function formattedPeriod(): string
    {
        return Carbon::createFromFormat(
            'Y-m',
            $this->period
        )->translatedFormat('F Y');
    }

    /**
     * Все конечные статьи дерева.
     */
    private function leafCategories(): Collection
    {
        return FinancialCategory::query()
            ->whereDoesntHave('children')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
