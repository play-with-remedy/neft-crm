<x-filament-panels::page>
    @php
        $totals = $this->getTotals();

        $items = [
            ['label' => 'Выручка', 'value' => $totals['revenue'] ?? 0],
            ['label' => 'Прибыль', 'value' => $totals['profit'] ?? 0],
            ['label' => 'Наличные', 'value' => $totals['cash'] ?? 0],
            ['label' => 'Сертификаты', 'value' => $totals['certificates'] ?? 0],
            ['label' => 'Расходы', 'value' => $totals['expenses'] ?? 0],
            ['label' => 'Зарплаты', 'value' => $totals['staff_salary'] ?? 0],
        ];

        $formatMoney = function ($value): string {
            $value = (float) ($value ?? 0);

            $formatted = number_format($value, 2, ',', ' ');
            $formatted = rtrim($formatted, '0');
            $formatted = rtrim($formatted, ',');

            return $formatted . ' BYN';
        };
    @endphp

    <style>
        .cash-book-summary {
            margin-bottom: 24px;
        }

        .cash-book-summary__header {
            margin-bottom: 12px;
        }

        .cash-book-summary__title {
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            margin: 0;
        }

        .cash-book-summary__subtitle {
            font-size: 13px;
            color: #9ca3af;
            margin-top: 4px;
        }

        .cash-book-summary__grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }

        .cash-book-summary__card {
            background: #18181b;
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 14px;
            padding: 14px 16px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
        }

        .cash-book-summary__label {
            font-size: 12px;
            font-weight: 500;
            color: #a1a1aa;
            margin-bottom: 8px;
        }

        .cash-book-summary__value {
            font-size: 20px;
            line-height: 1.2;
            font-weight: 700;
            color: #ffffff;
            white-space: nowrap;
        }

        .cash-book-summary__card--profit .cash-book-summary__value {
            color: #22c55e;
        }

        .cash-book-summary__card--expenses .cash-book-summary__value,
        .cash-book-summary__card--salary .cash-book-summary__value {
            color: #f97316;
        }
    </style>

    <div class="cash-book-summary">
        <div class="cash-book-summary__header">
            <h2 class="cash-book-summary__title">
                Итого по выборке
            </h2>

            <div class="cash-book-summary__subtitle">
                Суммы пересчитываются с учетом выбранных фильтров
            </div>
        </div>

        <div class="cash-book-summary__grid">
            @foreach ($items as $item)
                @php
                    $modifier = match ($item['label']) {
                        'Прибыль' => 'cash-book-summary__card--profit',
                        'Расходы' => 'cash-book-summary__card--expenses',
                        'Зарплаты' => 'cash-book-summary__card--salary',
                        default => '',
                    };
                @endphp

                <div class="cash-book-summary__card {{ $modifier }}">
                    <div class="cash-book-summary__label">
                        {{ $item['label'] }}
                    </div>

                    <div class="cash-book-summary__value">
                        {{ $formatMoney($item['value']) }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>