<x-filament-panels::page>
    <style>
        .event-statistics-period {
            width: min(100%, 620px);
            margin-bottom: 22px;
        }

        .event-statistics-mode {
            display: inline-flex;
            gap: 8px;
            margin-bottom: 20px;
        }

        .event-statistics-mode__button {
            border: 1px solid rgb(209, 213, 219);
            border-radius: 7px;
            background: white;
            padding: 8px 14px;
            color: rgb(75, 85, 99);
            font-size: 13px;
            font-weight: 600;
        }

        .event-statistics-mode__button--active {
            border-color: rgb(245, 158, 11);
            background: rgb(245, 158, 11);
            color: white;
        }

        .dark .event-statistics-mode__button:not(.event-statistics-mode__button--active) {
            border-color: rgb(82, 82, 91);
            background: rgb(39, 39, 42);
            color: rgb(212, 212, 216);
        }

        .dark .event-statistics-mode__button:not(.event-statistics-mode__button--active):hover {
            background: rgb(63, 63, 70);
            color: white;
        }

        .event-statistics-summary {
            margin-bottom: 24px;
        }

        .event-statistics-summary__header {
            margin-bottom: 12px;
        }

        .event-statistics-summary__title {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        .event-statistics-summary__grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .event-statistics-summary__card--revenue {
            grid-column: 1 / -1;
            justify-self: start;
            width: fit-content;
            max-width: 100%;
        }

        .event-statistics-summary__card {
            border: 1px solid rgb(229, 231, 235);
            border-radius: 14px;
            background: white;
            padding: 14px 16px;
            box-shadow: 0 1px 2px rgb(0 0 0 / 5%);
        }

        .dark .event-statistics-summary__card {
            border-color: rgba(255, 255, 255, 0.10);
            background: #18181b;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
        }

        .event-statistics-summary__label {
            margin-bottom: 8px;
            color: rgb(107, 114, 128);
            font-size: 12px;
            font-weight: 500;
        }

        .dark .event-statistics-summary__label {
            color: #a1a1aa;
        }

        .event-statistics-summary__value {
            color: rgb(17, 24, 39);
            font-size: 20px;
            font-weight: 700;
            line-height: 1.2;
            white-space: nowrap;
        }

        .event-statistics-summary__comparison {
            display: flex;
            align-items: baseline;
            gap: 8px;
        }

        .event-statistics-summary__arrow,
        .event-statistics-table__arrow {
            color: rgb(245, 158, 11);
            font-weight: 700;
        }

        .event-statistics-summary__comparison .event-statistics-summary__value {
            font-size: 18px;
        }

        .dark .event-statistics-summary__value {
            color: #ffffff;
        }

        .event-statistics-label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 600;
        }

        .event-statistics-period-range {
            display: grid;
            grid-template-columns: minmax(150px, 1fr) minmax(150px, 1fr) auto;
            align-items: end;
            gap: 10px;
        }

        .event-statistics-period-range--single {
            grid-template-columns: minmax(150px, 1fr);
            width: min(100%, 280px);
        }

        .event-statistics-period-field label {
            display: block;
            margin-bottom: 5px;
            color: rgb(107, 114, 128);
            font-size: 12px;
        }

        .event-statistics-period-input {
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

        .dark .event-statistics-period-input {
            border-color: rgb(63, 63, 70);
            background: rgb(24, 24, 27);
            color: white;
        }

        .event-statistics-apply {
            box-sizing: border-box;
            display: inline-flex;
            height: 40px;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 7px;
            background: rgb(245, 158, 11);
            padding: 0 14px;
            color: white;
            font-size: 13px;
            font-weight: 600;
            line-height: 1;
            white-space: nowrap;
        }

        .event-statistics-apply:hover {
            background: rgb(217, 119, 6);
        }

        .event-statistics-error {
            margin-top: 6px;
            color: rgb(220, 38, 38);
            font-size: 13px;
        }

        .event-statistics-card {
            overflow: hidden;
            border: 1px solid rgb(229, 231, 235);
            border-radius: 12px;
            background: white;
        }

        .dark .event-statistics-card {
            border-color: rgb(63, 63, 70);
            background: rgb(24, 24, 27);
        }

        .event-statistics-title {
            padding: 16px 18px;
            border-bottom: 1px solid rgb(229, 231, 235);
            font-size: 16px;
            font-weight: 700;
        }

        .dark .event-statistics-title {
            border-color: rgb(63, 63, 70);
        }

        .event-statistics-table-wrap {
            overflow-x: auto;
        }

        .event-statistics-table {
            width: 100%;
            border-collapse: collapse;
        }

        .event-statistics-table th,
        .event-statistics-table td {
            padding: 13px 16px;
            border-bottom: 1px solid rgb(229, 231, 235);
            text-align: right;
            white-space: nowrap;
        }

        .dark .event-statistics-table th,
        .dark .event-statistics-table td {
            border-color: rgb(63, 63, 70);
        }

        .event-statistics-table th {
            background: rgb(249, 250, 251);
            color: rgb(75, 85, 99);
            font-size: 12px;
            font-weight: 700;
        }

        .dark .event-statistics-table th {
            background: rgb(39, 39, 42);
            color: rgb(212, 212, 216);
        }

        .event-statistics-table th:first-child,
        .event-statistics-table td:first-child {
            text-align: left;
        }

        .event-statistics-empty {
            height: 72px;
            color: rgb(107, 114, 128);
            text-align: center !important;
        }

        .event-statistics-chart {
            padding: 18px;
        }

        .event-statistics-chart__rows {
            display: grid;
            gap: 14px;
        }

        .event-statistics-chart__row {
            display: grid;
            grid-template-columns: minmax(120px, 190px) minmax(160px, 1fr) 56px;
            align-items: center;
            gap: 12px;
        }

        .event-statistics-chart__label {
            overflow: hidden;
            font-size: 13px;
            font-weight: 600;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .event-statistics-chart__track {
            overflow: hidden;
            height: 20px;
            border-radius: 6px;
            background: rgb(243, 244, 246);
        }

        .dark .event-statistics-chart__track {
            background: rgb(39, 39, 42);
        }

        .event-statistics-chart__bar {
            min-width: 0;
            height: 100%;
            border-radius: 6px;
            background: rgb(245, 158, 11);
            transition: width 200ms ease;
        }

        .event-statistics-chart__bar--secondary {
            background: rgb(59, 130, 246);
        }

        .event-statistics-chart__legend {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 20px;
            margin-bottom: 18px;
            color: rgb(107, 114, 128);
            font-size: 12px;
        }

        .event-statistics-chart__legend-item {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .event-statistics-chart__legend-color {
            width: 18px;
            height: 7px;
            border-radius: 999px;
            background: rgb(245, 158, 11);
        }

        .event-statistics-chart__legend-color--secondary {
            background: rgb(59, 130, 246);
        }

        .event-statistics-chart__compare-row {
            display: grid;
            grid-template-columns: minmax(120px, 190px) minmax(160px, 1fr);
            gap: 12px;
        }

        .event-statistics-chart__compare-bars {
            display: grid;
            gap: 6px;
        }

        .event-statistics-chart__compare-bar {
            display: grid;
            grid-template-columns: minmax(100px, 1fr) 48px;
            align-items: center;
            gap: 8px;
        }

        .event-statistics-chart__value {
            color: rgb(75, 85, 99);
            font-size: 13px;
            font-weight: 700;
            text-align: right;
        }

        .dark .event-statistics-chart__value {
            color: rgb(212, 212, 216);
        }

        .event-statistics-comparison {
            margin-top: 28px;
        }

        .event-statistics-comparison-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .event-statistics-comparison-divider {
            height: 1px;
            margin: 14px 4px;
            border: 0;
            background: linear-gradient(90deg, transparent, rgb(209, 213, 219) 8%, rgb(209, 213, 219) 92%, transparent);
        }

        .dark .event-statistics-comparison-divider {
            background: linear-gradient(90deg, transparent, rgb(82, 82, 91) 8%, rgb(82, 82, 91) 92%, transparent);
        }

        @media (max-width: 1024px) {
            .event-statistics-summary__grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .event-statistics-period,
            .event-statistics-period-range,
            .event-statistics-period-range--single {
                width: 100%;
            }

            .event-statistics-period-range,
            .event-statistics-period-range--single {
                grid-template-columns: 1fr;
            }

            .event-statistics-chart__row {
                grid-template-columns: 1fr 48px;
                gap: 8px;
            }

            .event-statistics-chart__label {
                grid-column: 1 / -1;
            }

            .event-statistics-comparison-grid {
                grid-template-columns: 1fr;
            }

            .event-statistics-summary__grid {
                grid-template-columns: 1fr;
            }

            .event-statistics-summary__card--revenue {
                grid-column: auto;
            }

            .event-statistics-chart__compare-row {
                grid-template-columns: 1fr;
                gap: 7px;
            }
        }
    </style>

    @php
        $formatNumber = fn ($value, int $decimals = 0): string => number_format((float) $value, $decimals, ',', ' ');
        $formatMoney = function ($value): string {
            $formatted = number_format((float) $value, 2, ',', ' ');

            return rtrim(rtrim($formatted, '0'), ',') . ' BYN';
        };
    @endphp

    <div class="event-statistics-mode">
        <button type="button" wire:click="setMode('single')" @class(['event-statistics-mode__button', 'event-statistics-mode__button--active' => $mode === 'single'])>Один месяц</button>
        <button type="button" wire:click="setMode('comparison')" @class(['event-statistics-mode__button', 'event-statistics-mode__button--active' => $mode === 'comparison'])>Сравнение месяцев</button>
    </div>

    @if ($mode === 'single')
        <div class="event-statistics-period">
            <div class="event-statistics-period-range event-statistics-period-range--single">
                <div class="event-statistics-period-field">
                    <label for="event-statistics-month">Месяц</label>
                    <input id="event-statistics-month" type="month" wire:model="pendingMonth" wire:change="applyMonth" x-on:click="$el.showPicker?.()" class="event-statistics-period-input">
                </div>
            </div>
        </div>

        <section class="event-statistics-summary" wire:loading.class="opacity-60" wire:target="applyMonth">
            <div class="event-statistics-summary__header"><h2 class="event-statistics-summary__title">Итого за {{ $selectedMonthLabel }}</h2></div>
            <div class="event-statistics-summary__grid">
                @foreach ([
                    ['label' => 'Вечеров', 'value' => $formatNumber($monthlyStats['evenings_count'] ?? 0)],
                    ['label' => 'Посещений', 'value' => $formatNumber($monthlyStats['visits_count'] ?? 0)],
                    ['label' => 'Уникальных игроков', 'value' => $formatNumber($monthlyStats['unique_players_count'] ?? 0)],
                    ['label' => 'Посещений на игрока', 'value' => $formatNumber($monthlyStats['visits_per_player'] ?? 0, 2)],
                    ['label' => 'Выручка за месяц', 'value' => $formatMoney($monthlyStats['revenue'] ?? 0)],
                ] as $item)
                    <div @class(['event-statistics-summary__card', 'event-statistics-summary__card--revenue' => $item['label'] === 'Выручка за месяц'])><div class="event-statistics-summary__label">{{ $item['label'] }}</div><div class="event-statistics-summary__value">{{ $item['value'] }}</div></div>
                @endforeach
            </div>
        </section>

        <section class="event-statistics-card" wire:loading.class="opacity-60" wire:target="applyMonth">
            <div class="event-statistics-title">Посещения по типам</div>
            <div class="event-statistics-chart">
                @if (count($visitsByType))
                    <div class="event-statistics-chart__rows">
                        @foreach ($visitsByType as $type)
                            <div class="event-statistics-chart__row" wire:key="event-statistics-type-{{ $loop->index }}">
                                <div class="event-statistics-chart__label" title="{{ $type['name'] }}">{{ $type['name'] }}</div>
                                <div class="event-statistics-chart__track"><div class="event-statistics-chart__bar" style="width: {{ $type['percentage'] }}%"></div></div>
                                <div class="event-statistics-chart__value">{{ $formatNumber($type['visits_count']) }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="event-statistics-empty">Типы мероприятий пока не созданы.</div>
                @endif
            </div>
        </section>

        <section class="event-statistics-card" style="margin-top: 16px" wire:loading.class="opacity-60" wire:target="applyMonth">
            <div class="event-statistics-title">Статистика по типам</div>
            <div class="event-statistics-table-wrap"><table class="event-statistics-table">
                <thead><tr><th>Тип</th><th>Вечера</th><th>Посещения</th><th>Уникальные</th><th>Выручка</th></tr></thead>
                <tbody>@forelse ($typeStats as $type)<tr wire:key="event-statistics-type-row-{{ $type['id'] }}"><td>{{ $type['name'] }}</td><td>{{ $formatNumber($type['evenings_count']) }}</td><td>{{ $formatNumber($type['visits_count']) }}</td><td>{{ $formatNumber($type['unique_players_count']) }}</td><td>{{ $formatMoney($type['revenue']) }}</td></tr>@empty<tr><td colspan="5" class="event-statistics-empty">Типы мероприятий пока не созданы.</td></tr>@endforelse</tbody>
            </table></div>
        </section>
    @else
        <div class="event-statistics-period">
            <div class="event-statistics-period-range" style="grid-template-columns: repeat(2, minmax(150px, 1fr))">
                <div class="event-statistics-period-field"><label for="event-statistics-compare-a">Первый месяц</label><input id="event-statistics-compare-a" type="month" wire:model="comparisonMonthA" wire:change="applyComparison" x-on:click="$el.showPicker?.()" class="event-statistics-period-input"></div>
                <div class="event-statistics-period-field"><label for="event-statistics-compare-b">Второй месяц</label><input id="event-statistics-compare-b" type="month" wire:model="comparisonMonthB" wire:change="applyComparison" x-on:click="$el.showPicker?.()" class="event-statistics-period-input"></div>
            </div>
        </div>

        <section class="event-statistics-summary" wire:loading.class="opacity-60" wire:target="applyComparison,setMode">
            <div class="event-statistics-summary__header"><h2 class="event-statistics-summary__title">Сравнение общих показателей</h2></div>
            <div class="event-statistics-summary__grid">
                @foreach ([
                    ['label' => 'Вечеров', 'key' => 'evenings_count', 'format' => 'number'],
                    ['label' => 'Посещений', 'key' => 'visits_count', 'format' => 'number'],
                    ['label' => 'Уникальных игроков', 'key' => 'unique_players_count', 'format' => 'number'],
                    ['label' => 'Посещений на игрока', 'key' => 'visits_per_player', 'format' => 'decimal'],
                    ['label' => 'Выручка за месяц', 'key' => 'revenue', 'format' => 'money'],
                ] as $item)
                    @php
                        $valueA = $item['format'] === 'money' ? $formatMoney($comparisonStatsA[$item['key']] ?? 0) : $formatNumber($comparisonStatsA[$item['key']] ?? 0, $item['format'] === 'decimal' ? 2 : 0);
                        $valueB = $item['format'] === 'money' ? $formatMoney($comparisonStatsB[$item['key']] ?? 0) : $formatNumber($comparisonStatsB[$item['key']] ?? 0, $item['format'] === 'decimal' ? 2 : 0);
                    @endphp
                    <div @class(['event-statistics-summary__card', 'event-statistics-summary__card--revenue' => $item['key'] === 'revenue'])>
                        <div class="event-statistics-summary__label">{{ $item['label'] }}</div>
                        <div class="event-statistics-summary__comparison">
                            <span class="event-statistics-summary__value">{{ $valueA }}</span>
                            <span class="event-statistics-summary__arrow" aria-hidden="true">→</span>
                            <span class="event-statistics-summary__value">{{ $valueB }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="event-statistics-card" wire:loading.class="opacity-60" wire:target="applyComparison,setMode">
            <div class="event-statistics-title">Посещения по типам</div>
            <div class="event-statistics-chart">
                <div class="event-statistics-chart__legend">
                    <span class="event-statistics-chart__legend-item"><span class="event-statistics-chart__legend-color"></span>{{ $comparisonLabelA }}</span>
                    <span class="event-statistics-chart__legend-item"><span class="event-statistics-chart__legend-color event-statistics-chart__legend-color--secondary"></span>{{ $comparisonLabelB }}</span>
                </div>
                @if (count($comparisonTypeStats))
                    <div class="event-statistics-chart__rows">
                        @foreach ($comparisonTypeStats as $type)
                            <div class="event-statistics-chart__compare-row" wire:key="comparison-chart-{{ $type['id'] }}">
                                <div class="event-statistics-chart__label" title="{{ $type['name'] }}">{{ $type['name'] }}</div>
                                <div class="event-statistics-chart__compare-bars">
                                    <div class="event-statistics-chart__compare-bar">
                                        <div class="event-statistics-chart__track"><div class="event-statistics-chart__bar" style="width: {{ $type['percentage_a'] }}%"></div></div>
                                        <div class="event-statistics-chart__value">{{ $formatNumber($type['a']['visits_count']) }}</div>
                                    </div>
                                    <div class="event-statistics-chart__compare-bar">
                                        <div class="event-statistics-chart__track"><div class="event-statistics-chart__bar event-statistics-chart__bar--secondary" style="width: {{ $type['percentage_b'] }}%"></div></div>
                                        <div class="event-statistics-chart__value">{{ $formatNumber($type['b']['visits_count']) }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="event-statistics-empty">Типы мероприятий пока не созданы.</div>
                @endif
            </div>
        </section>

        <section class="event-statistics-card" style="margin-top: 16px" wire:loading.class="opacity-60" wire:target="applyComparison,setMode">
            <div class="event-statistics-title">Статистика по типам</div>
            <div class="event-statistics-table-wrap"><table class="event-statistics-table">
                <thead><tr><th>Тип</th><th>Вечера</th><th>Посещения</th><th>Уникальные</th><th>Выручка</th></tr></thead>
                <tbody>
                    @forelse ($comparisonTypeStats as $type)
                        <tr wire:key="comparison-type-row-{{ $type['id'] }}">
                            <td>{{ $type['name'] }}</td>
                            <td>{{ $formatNumber($type['a']['evenings_count']) }} <span class="event-statistics-table__arrow">→</span> {{ $formatNumber($type['b']['evenings_count']) }}</td>
                            <td>{{ $formatNumber($type['a']['visits_count']) }} <span class="event-statistics-table__arrow">→</span> {{ $formatNumber($type['b']['visits_count']) }}</td>
                            <td>{{ $formatNumber($type['a']['unique_players_count']) }} <span class="event-statistics-table__arrow">→</span> {{ $formatNumber($type['b']['unique_players_count']) }}</td>
                            <td>{{ $formatMoney($type['a']['revenue']) }} <span class="event-statistics-table__arrow">→</span> {{ $formatMoney($type['b']['revenue']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="event-statistics-empty">Типы мероприятий пока не созданы.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </section>
    @endif
</x-filament-panels::page>
