<x-filament-panels::page>
    <style>
        .monthly-finances-period {
            width: min(100%, 620px);
            margin-bottom: 22px;
        }

        .monthly-finances-label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 600;
        }

        .monthly-finances-period-range {
            display: grid;
            grid-template-columns: minmax(150px, 1fr) minmax(150px, 1fr) auto;
            align-items: end;
            gap: 10px;
        }

        .monthly-finances-period-field label {
            display: block;
            margin-bottom: 5px;
            color: rgb(107, 114, 128);
            font-size: 12px;
        }

        .monthly-finances-period-input {
            box-sizing: border-box;
            width: 100%;
            height: 40px;
            border: 1px solid rgb(209, 213, 219);
            border-radius: 8px;
            background: white;
            padding: 0 12px;
            color: rgb(17, 24, 39);
            cursor: pointer;
        }

        .dark .monthly-finances-period-input {
            border-color: rgb(63, 63, 70);
            background: rgb(24, 24, 27);
            color: white;
        }

        .monthly-finances-apply {
            box-sizing: border-box;
            display: inline-flex;
            height: 40px;
            align-items: center;
            align-self: end;
            justify-content: center;
            border-radius: 7px;
            border: 0;
            background: rgb(245, 158, 11);
            padding: 0 14px;
            color: white;
            font-size: 13px;
            font-weight: 600;
            line-height: 1;
            white-space: nowrap;
        }

        .monthly-finances-apply:hover {
            background: rgb(217, 119, 6);
        }

        .monthly-finances-error {
            margin-top: 6px;
            color: rgb(220, 38, 38);
            font-size: 13px;
        }

        .monthly-finances-card {
            overflow: hidden;
            border: 1px solid rgb(229, 231, 235);
            border-radius: 12px;
            background: white;
        }

        .dark .monthly-finances-card {
            border-color: rgb(63, 63, 70);
            background: rgb(24, 24, 27);
        }

        .monthly-finances-title {
            padding: 16px 18px;
            border-bottom: 1px solid rgb(229, 231, 235);
            font-size: 16px;
            font-weight: 700;
        }

        .dark .monthly-finances-title {
            border-color: rgb(63, 63, 70);
        }

        .monthly-finances-table-wrap {
            overflow-x: auto;
        }

        .monthly-finances-table {
            width: 100%;
            border-collapse: collapse;
        }

        .monthly-finances-table th,
        .monthly-finances-table td {
            padding: 13px 16px;
            border-bottom: 1px solid rgb(229, 231, 235);
            text-align: right;
            white-space: nowrap;
        }

        .dark .monthly-finances-table th,
        .dark .monthly-finances-table td {
            border-color: rgb(63, 63, 70);
        }

        .monthly-finances-table th {
            background: rgb(249, 250, 251);
            color: rgb(75, 85, 99);
            font-size: 12px;
            font-weight: 700;
        }

        .monthly-finances-sort {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            color: inherit;
            font: inherit;
            font-weight: inherit;
            cursor: pointer;
        }

        .monthly-finances-sort-indicator {
            min-width: 0.75rem;
            color: rgb(245 158 11);
        }

        .dark .monthly-finances-table th {
            background: rgb(39, 39, 42);
            color: rgb(212, 212, 216);
        }

        .monthly-finances-table th:first-child,
        .monthly-finances-table td:first-child {
            text-align: left;
        }

        .monthly-finances-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .monthly-finances-positive {
            color: rgb(22, 163, 74);
            font-weight: 600;
        }

        .monthly-finances-negative {
            color: rgb(220, 38, 38);
            font-weight: 600;
        }

        .monthly-finances-muted {
            color: rgb(107, 114, 128);
        }

        .monthly-finances-comparison {
            margin-top: 28px;
        }

        .monthly-finances-comparison-grid {
            display: grid;
            gap: 16px;
            margin-top: 16px;
        }

        .monthly-finances-comparison-divider {
            height: 1px;
            margin: 14px 4px;
            border: 0;
            background: linear-gradient(
                90deg,
                transparent,
                rgb(209, 213, 219) 8%,
                rgb(209, 213, 219) 92%,
                transparent
            );
        }

        .dark .monthly-finances-comparison-divider {
            background: linear-gradient(
                90deg,
                transparent,
                rgb(82, 82, 91) 8%,
                rgb(82, 82, 91) 92%,
                transparent
            );
        }

        .monthly-finances-category-parent td:first-child {
            font-weight: 700;
        }

        .monthly-finances-category-toggle {
            display: inline-flex;
            width: 20px;
            height: 20px;
            margin-right: 4px;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            color: rgb(107, 114, 128);
            vertical-align: middle;
        }

        .monthly-finances-category-toggle:hover {
            background: rgb(243, 244, 246);
            color: rgb(17, 24, 39);
        }

        .dark .monthly-finances-category-toggle:hover {
            background: rgb(63, 63, 70);
            color: white;
        }

        .monthly-finances-category-arrow {
            display: inline-block;
            transition: transform 150ms ease;
        }

        @media (max-width: 640px) {
            .monthly-finances-period,
            .monthly-finances-period-range {
                width: 100%;
            }

            .monthly-finances-period-range {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="monthly-finances-period">
        <span class="monthly-finances-label">Период</span>

        <div class="monthly-finances-period-range">
            <div class="monthly-finances-period-field">
                <label for="monthly-finances-from">С месяца</label>
                <input
                    id="monthly-finances-from"
                    type="month"
                    wire:model="pendingPeriodFrom"
                    x-on:click="$el.showPicker?.()"
                    class="monthly-finances-period-input"
                >
            </div>

            <div class="monthly-finances-period-field">
                <label for="monthly-finances-until">По месяц</label>
                <input
                    id="monthly-finances-until"
                    type="month"
                    wire:model="pendingPeriodUntil"
                    x-on:click="$el.showPicker?.()"
                    class="monthly-finances-period-input"
                >
            </div>

            <button
                type="button"
                class="monthly-finances-apply"
                wire:click="applyPeriodRange"
            >
                Применить
            </button>
        </div>

        @error('periodRange')
            <div class="monthly-finances-error">{{ $message }}</div>
        @enderror
    </div>

    <section
        class="monthly-finances-card"
        wire:loading.class="opacity-60"
        wire:target="applyPeriodRange"
    >
        <div class="monthly-finances-title">Финансовая таблица</div>

        <div class="monthly-finances-table-wrap">
            <table class="monthly-finances-table">
                <thead>
                    <tr>
                        @foreach ([
                            'month' => 'Месяц',
                            'revenue' => 'Выручка',
                            'expenses' => 'Расходы',
                            'profit' => 'Прибыль',
                            'margin' => 'Маржа',
                            'revenue_change' => 'Выручка к пред. месяцу',
                        ] as $column => $label)
                            <th>
                                <button
                                    type="button"
                                    class="monthly-finances-sort"
                                    wire:click="sortTable('{{ $column }}')"
                                >
                                    <span>{{ $label }}</span>
                                    <span class="monthly-finances-sort-indicator" aria-hidden="true">
                                        @if ($sortColumn === $column)
                                            {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                        @endif
                                    </span>
                                </button>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        @php
                            $money = fn ($value): string => number_format((float) $value, 2, ',', ' ') . ' BYN';
                            $change = $row['revenue_change'];
                        @endphp
                        <tr wire:key="monthly-finances-{{ $row['month'] }}">
                            <td>{{ $row['label'] }}</td>
                            <td>{{ $money($row['revenue']) }}</td>
                            <td>{{ $money($row['expenses']) }}</td>
                            <td class="{{ $row['profit'] < 0 ? 'monthly-finances-negative' : ($row['profit'] > 0 ? 'monthly-finances-positive' : '') }}">
                                {{ $money($row['profit']) }}
                            </td>
                            <td class="{{ ($row['margin'] ?? 0) < 0 ? 'monthly-finances-negative' : (($row['margin'] ?? 0) > 0 ? 'monthly-finances-positive' : '') }}">
                                {{ $row['margin'] === null ? '—' : number_format($row['margin'], 1, ',', ' ') . '%' }}
                            </td>
                            <td class="{{ $change === null ? 'monthly-finances-muted' : ($change < 0 ? 'monthly-finances-negative' : 'monthly-finances-positive') }}">
                                @if ($change === null)
                                    —
                                @else
                                    {{ $change >= 0 ? '+' : '−' }}{{ number_format(abs($change), 1, ',', ' ') }}%
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="monthly-finances-comparison">
        <div class="monthly-finances-period">
            <span class="monthly-finances-label">Сравнение месяцев</span>

            <div class="monthly-finances-period-range">
                <div class="monthly-finances-period-field">
                    <label for="monthly-finances-compare-a">Первый месяц</label>
                    <input
                        id="monthly-finances-compare-a"
                        type="month"
                        wire:model="comparisonMonthA"
                        x-on:click="$el.showPicker?.()"
                        class="monthly-finances-period-input"
                    >
                </div>

                <div class="monthly-finances-period-field">
                    <label for="monthly-finances-compare-b">Второй месяц</label>
                    <input
                        id="monthly-finances-compare-b"
                        type="month"
                        wire:model="comparisonMonthB"
                        x-on:click="$el.showPicker?.()"
                        class="monthly-finances-period-input"
                    >
                </div>

                <button
                    type="button"
                    class="monthly-finances-apply"
                    wire:click="applyComparison"
                >
                    Сравнить
                </button>
            </div>

            @error('comparisonRange')
                <div class="monthly-finances-error">{{ $message }}</div>
            @enderror
        </div>

        @php
            $comparisonMoney = fn ($value): string => number_format((float) $value, 2, ',', ' ') . ' BYN';
            $signedMoney = fn ($value): string => ($value >= 0 ? '+' : '−') . number_format(abs((float) $value), 2, ',', ' ') . ' BYN';
            $signedPercent = fn ($value): string => ($value >= 0 ? '+' : '−') . number_format(abs((float) $value), 1, ',', ' ') . '%';
            $collapsedCategories = collect($comparisonCategoryRows)
                ->where('is_parent', true)
                ->mapWithKeys(fn ($row) => [(string) $row['id'] => true])
                ->all();
        @endphp

        <div
            class="monthly-finances-comparison-grid"
            wire:loading.class="opacity-60"
            wire:target="applyComparison"
        >
            <div class="monthly-finances-card">
                <div class="monthly-finances-title">Общие показатели</div>
                <div class="monthly-finances-table-wrap">
                    <table class="monthly-finances-table">
                        <thead>
                            <tr>
                                <th>Показатель</th>
                                <th>{{ $comparisonLabelA }}</th>
                                <th>{{ $comparisonLabelB }}</th>
                                <th>Разница</th>
                                <th>Изменение</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($comparisonSummaryRows as $row)
                                <tr wire:key="monthly-finances-summary-{{ $loop->index }}">
                                    <td>{{ $row['label'] }}</td>
                                    <td>{{ $row['type'] === 'margin' ? ($row['value_a'] === null ? '—' : number_format($row['value_a'], 1, ',', ' ') . '%') : $comparisonMoney($row['value_a']) }}</td>
                                    <td>{{ $row['type'] === 'margin' ? ($row['value_b'] === null ? '—' : number_format($row['value_b'], 1, ',', ' ') . '%') : $comparisonMoney($row['value_b']) }}</td>
                                    <td class="{{ ($row['difference'] ?? 0) < 0 ? 'monthly-finances-negative' : (($row['difference'] ?? 0) > 0 ? 'monthly-finances-positive' : '') }}">
                                        @if ($row['difference'] === null)
                                            —
                                        @elseif ($row['type'] === 'margin')
                                            {{ $row['difference'] >= 0 ? '+' : '−' }}{{ number_format(abs($row['difference']), 1, ',', ' ') }} п.п.
                                        @else
                                            {{ $signedMoney($row['difference']) }}
                                        @endif
                                    </td>
                                    <td class="{{ ($row['change'] ?? 0) < 0 ? 'monthly-finances-negative' : (($row['change'] ?? 0) > 0 ? 'monthly-finances-positive' : 'monthly-finances-muted') }}">
                                        {{ $row['change'] === null ? '—' : $signedPercent($row['change']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <hr class="monthly-finances-comparison-divider">

            <div class="monthly-finances-card">
                <div class="monthly-finances-title">Сравнение категорий расходов</div>
                <div class="monthly-finances-table-wrap">
                    <table
                        class="monthly-finances-table"
                        x-data="{ collapsed: @js($collapsedCategories) }"
                    >
                        <thead>
                            <tr>
                                <th>Категория</th>
                                <th>{{ $comparisonLabelA }}</th>
                                <th>{{ $comparisonLabelB }}</th>
                                <th>Разница</th>
                                <th>Изменение</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($comparisonCategoryRows as $row)
                                <tr
                                    wire:key="monthly-finances-category-{{ $row['id'] }}"
                                    @class(['monthly-finances-category-parent' => $row['is_parent']])
                                    x-show='@json($row['ancestor_ids']).every((id) => !collapsed[id])'
                                >
                                    <td style="padding-left: {{ 16 + (($row['level'] - 1) * 22) }}px">
                                        @if ($row['is_parent'])
                                            <button
                                                type="button"
                                                class="monthly-finances-category-toggle"
                                                x-on:click="collapsed[{{ $row['id'] }}] = !collapsed[{{ $row['id'] }}]"
                                                x-bind:aria-expanded="!collapsed[{{ $row['id'] }}]"
                                                aria-label="Свернуть или раскрыть категорию"
                                            >
                                                <span
                                                    class="monthly-finances-category-arrow"
                                                    x-bind:style="collapsed[{{ $row['id'] }}] ? '' : 'transform: rotate(90deg)'"
                                                >›</span>
                                            </button>
                                        @else
                                            <span style="display: inline-block; width: 24px"></span>
                                        @endif
                                        {{ $row['label'] }}
                                    </td>
                                    <td>{{ $comparisonMoney($row['value_a']) }}</td>
                                    <td>{{ $comparisonMoney($row['value_b']) }}</td>
                                    <td class="{{ $row['difference'] < 0 ? 'monthly-finances-negative' : ($row['difference'] > 0 ? 'monthly-finances-positive' : '') }}">
                                        {{ $signedMoney($row['difference']) }}
                                    </td>
                                    <td class="{{ ($row['change'] ?? 0) < 0 ? 'monthly-finances-negative' : (($row['change'] ?? 0) > 0 ? 'monthly-finances-positive' : 'monthly-finances-muted') }}">
                                        {{ $row['change'] === null ? '—' : $signedPercent($row['change']) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="monthly-finances-muted">Категории расходов пока не созданы.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</x-filament-panels::page>
