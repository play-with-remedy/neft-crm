<x-filament-panels::page>
    <style>
        .ltv-period { width: min(100%, 620px); margin-bottom: 24px; }
        .ltv-period__label { display: block; margin-bottom: 6px; font-size: 14px; font-weight: 600; }
        .ltv-period__range { display: grid; grid-template-columns: minmax(150px, 1fr) minmax(150px, 1fr) auto; align-items: end; gap: 10px; }
        .ltv-period__field label { display: block; margin-bottom: 5px; color: rgb(107, 114, 128); font-size: 12px; }
        .ltv-period__input { box-sizing: border-box; width: 100%; height: 40px; border: 1px solid rgb(209, 213, 219); border-radius: 8px; background: white; padding: 0 12px; color: rgb(17, 24, 39); cursor: pointer; }
        .dark .ltv-period__input { border-color: rgb(63, 63, 70); background: rgb(24, 24, 27); color: white; }
        .ltv-period__apply { display: inline-flex; height: 40px; align-items: center; justify-content: center; border-radius: 7px; background: rgb(245, 158, 11); padding: 0 14px; color: white; font-size: 13px; font-weight: 600; }
        .ltv-period__apply:hover { background: rgb(217, 119, 6); }
        .ltv-period__error { margin-top: 6px; color: rgb(220, 38, 38); font-size: 13px; }

        .ltv-heading { margin-bottom: 14px; }
        .ltv-heading__title { margin: 0; font-size: 18px; font-weight: 700; }
        .ltv-heading__subtitle { margin-top: 4px; color: rgb(107, 114, 128); font-size: 13px; }
        .ltv-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 28px; }
        .ltv-summary__card { border: 1px solid rgb(229, 231, 235); border-radius: 14px; background: white; padding: 14px 16px; box-shadow: 0 1px 2px rgb(0 0 0 / 5%); }
        .ltv-summary__card--lifetime { grid-column: 1 / -1; justify-self: start; width: fit-content; max-width: 100%; }
        .dark .ltv-summary__card { border-color: rgba(255, 255, 255, .1); background: #18181b; box-shadow: 0 1px 2px rgba(0, 0, 0, .25); }
        .ltv-summary__label { margin-bottom: 8px; color: rgb(107, 114, 128); font-size: 12px; font-weight: 500; }
        .dark .ltv-summary__label { color: #a1a1aa; }
        .ltv-summary__value { color: rgb(17, 24, 39); font-size: 20px; font-weight: 700; line-height: 1.2; white-space: nowrap; }
        .dark .ltv-summary__value { color: white; }

        .ltv-table-card { overflow: hidden; border: 1px solid rgb(229, 231, 235); border-radius: 12px; background: white; }
        .dark .ltv-table-card { border-color: rgb(63, 63, 70); background: rgb(24, 24, 27); }
        .ltv-table__heading { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 18px; border-bottom: 1px solid rgb(229, 231, 235); }
        .dark .ltv-table__heading { border-color: rgb(63, 63, 70); }
        .ltv-table__title { margin: 0; font-size: 16px; font-weight: 700; }
        .ltv-page-size { height: 34px; border: 1px solid rgb(209, 213, 219); border-radius: 7px; background: white; padding: 0 9px; color: rgb(17, 24, 39); font-size: 12px; }
        .dark .ltv-page-size { border-color: rgb(63, 63, 70); background: rgb(39, 39, 42); color: white; }
        .ltv-table-wrap { overflow-x: auto; }
        .ltv-table { width: 100%; border-collapse: collapse; }
        .ltv-table th, .ltv-table td { padding: 13px 16px; border-bottom: 1px solid rgb(229, 231, 235); text-align: right; white-space: nowrap; }
        .dark .ltv-table th, .dark .ltv-table td { border-color: rgb(63, 63, 70); }
        .ltv-table th { background: rgb(249, 250, 251); color: rgb(75, 85, 99); font-size: 12px; font-weight: 700; }
        .ltv-table__sort { display: inline-flex; align-items: center; gap: 5px; color: inherit; font: inherit; font-weight: inherit; cursor: pointer; }
        .ltv-table__sort-indicator { width: 10px; color: rgb(245, 158, 11); }
        .dark .ltv-table th { background: rgb(39, 39, 42); color: rgb(212, 212, 216); }
        .ltv-table th:first-child, .ltv-table td:first-child { text-align: left; }
        .ltv-table tbody tr:last-child td { border-bottom: 0; }
        .ltv-table__empty { color: rgb(107, 114, 128); text-align: center !important; }
        .ltv-pagination { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 14px 18px; border-top: 1px solid rgb(229, 231, 235); }
        .dark .ltv-pagination { border-color: rgb(63, 63, 70); }
        .ltv-pagination button { border: 1px solid rgb(209, 213, 219); border-radius: 7px; padding: 6px 10px; font-size: 12px; font-weight: 600; }
        .dark .ltv-pagination button { border-color: rgb(82, 82, 91); }
        .ltv-pagination button:disabled { cursor: not-allowed; opacity: .4; }
        .ltv-pagination__status { color: rgb(107, 114, 128); font-size: 12px; }

        .ltv-chart-card { overflow: hidden; margin-top: 20px; border: 1px solid rgb(229, 231, 235); border-radius: 12px; background: white; }
        .dark .ltv-chart-card { border-color: rgb(63, 63, 70); background: rgb(24, 24, 27); }
        .ltv-chart__header { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 12px 18px; border-bottom: 1px solid rgb(229, 231, 235); }
        .dark .ltv-chart__header { border-color: rgb(63, 63, 70); }
        .ltv-chart__title { font-size: 16px; font-weight: 700; }
        .ltv-chart__tabs { display: inline-flex; gap: 6px; }
        .ltv-chart__tab { border: 1px solid rgb(209, 213, 219); border-radius: 7px; background: white; padding: 7px 10px; color: rgb(75, 85, 99); font-size: 12px; font-weight: 600; }
        .ltv-chart__tab--active { border-color: rgb(245, 158, 11); background: rgb(245, 158, 11); color: white; }
        .dark .ltv-chart__tab:not(.ltv-chart__tab--active) { border-color: rgb(82, 82, 91); background: rgb(39, 39, 42); color: rgb(212, 212, 216); }
        .ltv-chart__wrap { overflow-x: auto; padding: 12px 10px 6px; }
        .ltv-chart { display: block; height: 320px; }
        .ltv-chart__grid { stroke: rgb(229, 231, 235); stroke-width: 1; }
        .dark .ltv-chart__grid { stroke: rgb(63, 63, 70); }
        .ltv-chart__axis-label { fill: rgb(107, 114, 128); font-size: 11px; }
        .dark .ltv-chart__axis-label { fill: rgb(161, 161, 170); }
        .ltv-chart__area { fill: rgb(245 158 11 / 12%); }
        .ltv-chart__line { fill: none; stroke: rgb(245, 158, 11); stroke-width: 3; stroke-linecap: round; stroke-linejoin: round; }
        .ltv-chart__point { fill: white; stroke: rgb(245, 158, 11); stroke-width: 3; }
        .dark .ltv-chart__point { fill: rgb(24, 24, 27); }

        @media (max-width: 1024px) {
            .ltv-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 640px) {
            .ltv-period, .ltv-period__range { width: 100%; }
            .ltv-period__range { grid-template-columns: 1fr; }
            .ltv-summary { grid-template-columns: 1fr; }
            .ltv-chart__header { align-items: flex-start; flex-direction: column; }
        }
    </style>

    @php
        $money = fn ($value): string => number_format((float) $value, 2, ',', ' ') . ' BYN';
        $number = fn ($value, int $decimals = 0): string => number_format((float) $value, $decimals, ',', ' ');
        $chartRows = collect($rows)->sortBy('month')->values();
        $chartWidth = max(720, 110 + ($chartRows->count() * 90));
        $chartHeight = 320;
        $chartLeft = 82;
        $chartRight = 24;
        $chartTop = 24;
        $chartBottom = 58;
        $chartPlotWidth = $chartWidth - $chartLeft - $chartRight;
        $chartPlotHeight = $chartHeight - $chartTop - $chartBottom;
        $buildChart = function (string $metric) use ($chartRows, $chartLeft, $chartPlotWidth, $chartTop, $chartPlotHeight) {
            $maximum = max(1, (float) $chartRows->max($metric));
            $points = $chartRows->map(function ($row, $index) use ($metric, $maximum, $chartRows, $chartLeft, $chartPlotWidth, $chartTop, $chartPlotHeight) {
                $x = $chartRows->count() <= 1
                    ? $chartLeft + ($chartPlotWidth / 2)
                    : $chartLeft + (($chartPlotWidth / ($chartRows->count() - 1)) * $index);
                $y = $chartTop + $chartPlotHeight - (($row[$metric] / $maximum) * $chartPlotHeight);

                return ['x' => round($x, 2), 'y' => round($y, 2), 'row' => $row];
            });
            $polyline = $points->map(fn ($point) => $point['x'] . ',' . $point['y'])->implode(' ');

            return [
                'maximum' => $maximum,
                'points' => $points,
                'polyline' => $polyline,
                'area' => $points->isEmpty()
                    ? ''
                    : $points->first()['x'] . ',' . ($chartTop + $chartPlotHeight) . ' ' . $polyline . ' ' . $points->last()['x'] . ',' . ($chartTop + $chartPlotHeight),
            ];
        };
        $charts = [
            'ltv' => ['label' => 'Средний LTV', 'metric' => 'average_ltv', 'chart' => $buildChart('average_ltv')],
            'revenue' => ['label' => 'Выручка новичков', 'metric' => 'revenue', 'chart' => $buildChart('revenue')],
        ];
        $visibleRows = $this->getVisibleRows();
        $tablePages = $this->getTablePages();
    @endphp

    <div class="ltv-period">
        <span class="ltv-period__label">Период первого визита</span>
        <div class="ltv-period__range">
            <div class="ltv-period__field"><label for="ltv-period-from">С месяца</label><input id="ltv-period-from" type="month" wire:model="pendingPeriodFrom" x-on:click="$el.showPicker?.()" class="ltv-period__input"></div>
            <div class="ltv-period__field"><label for="ltv-period-until">По месяц</label><input id="ltv-period-until" type="month" wire:model="pendingPeriodUntil" x-on:click="$el.showPicker?.()" class="ltv-period__input"></div>
            <button type="button" class="ltv-period__apply" wire:click="applyPeriodRange">Применить</button>
        </div>
        @error('periodRange')<div class="ltv-period__error">{{ $message }}</div>@enderror
    </div>

    <div wire:loading.class="opacity-60" wire:target="applyPeriodRange">
        <div class="ltv-heading">
            <h2 class="ltv-heading__title">Новые игроки — ключевые метрики</h2>
            <div class="ltv-heading__subtitle">Анализ игроков по периоду первого визита</div>
        </div>

        <div class="ltv-summary">
            @foreach ([
                ['label' => 'Новые игроки', 'value' => $number($summary['new_players_count'] ?? 0)],
                ['label' => 'Выручка от новичков', 'value' => $money($summary['revenue'] ?? 0)],
                ['label' => 'Средний LTV', 'value' => $money($summary['average_ltv'] ?? 0)],
                ['label' => 'Среднее количество посещений', 'value' => $number($summary['average_visits'] ?? 0, 1)],
                ['label' => 'Средняя продолжительность жизни', 'value' => $number($summary['average_lifetime_days'] ?? 0, 1) . ' дн.'],
            ] as $item)
                <div @class(['ltv-summary__card', 'ltv-summary__card--lifetime' => $item['label'] === 'Средняя продолжительность жизни'])><div class="ltv-summary__label">{{ $item['label'] }}</div><div class="ltv-summary__value">{{ $item['value'] }}</div></div>
            @endforeach
        </div>

        <section class="ltv-table-card" wire:loading.class="opacity-60" wire:target="sortTable,previousTablePage,nextTablePage,tablePerPage">
            <div class="ltv-table__heading">
                <h2 class="ltv-table__title">LTV новых игроков</h2>
                <select class="ltv-page-size" wire:model.live="tablePerPage" aria-label="Количество отображаемых месяцев"><option value="3">3 месяца</option><option value="6">6 месяцев</option><option value="12">12 месяцев</option><option value="all">Весь период</option></select>
            </div>
            <div class="ltv-table-wrap"><table class="ltv-table">
                <thead><tr>
                    <th>Месяц первого визита</th>
                    @foreach ([
                        'new_players_count' => 'Новые игроки',
                        'revenue' => 'Выручка от новичков',
                        'average_ltv' => 'Средний LTV',
                        'average_visits' => 'Среднее посещение',
                        'average_lifetime_days' => 'Средняя продолжительность',
                    ] as $column => $label)
                        <th><button type="button" class="ltv-table__sort" wire:click="sortTable('{{ $column }}')"><span>{{ $label }}</span><span class="ltv-table__sort-indicator">@if ($sortColumn === $column){{ $sortDirection === 'asc' ? '↑' : '↓' }}@endif</span></button></th>
                    @endforeach
                </tr></thead>
                <tbody>
                    @forelse ($visibleRows as $row)
                        <tr wire:key="ltv-row-{{ $row['month'] }}"><td>{{ $row['label'] }}</td><td>{{ $number($row['new_players_count']) }}</td><td>{{ $money($row['revenue']) }}</td><td>{{ $money($row['average_ltv']) }}</td><td>{{ $number($row['average_visits'], 1) }}</td><td>{{ $number($row['average_lifetime_days'], 1) }} дн.</td></tr>
                    @empty
                        <tr><td colspan="6" class="ltv-table__empty">Пока нет игроков с датой первого посещения.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            @if ($tablePages > 1)
                <div class="ltv-pagination"><button type="button" wire:click="previousTablePage" @disabled($tablePage <= 1)>Назад</button><span class="ltv-pagination__status">Страница {{ $tablePage }} из {{ $tablePages }}</span><button type="button" wire:click="nextTablePage" @disabled($tablePage >= $tablePages)>Вперёд</button></div>
            @endif
        </section>

        <section class="ltv-chart-card" wire:key="ltv-chart-{{ $periodFrom }}-{{ $periodUntil }}" x-data="{ metric: 'ltv' }">
            <div class="ltv-chart__header">
                <div class="ltv-chart__title">Динамика новых игроков</div>
                <div class="ltv-chart__tabs">
                    @foreach ($charts as $key => $dataset)
                        <button type="button" x-on:click="metric = '{{ $key }}'" x-bind:class="{ 'ltv-chart__tab--active': metric === '{{ $key }}' }" class="ltv-chart__tab">{{ $dataset['label'] }}</button>
                    @endforeach
                </div>
            </div>

            @foreach ($charts as $key => $dataset)
                <div class="ltv-chart__wrap" x-show="metric === '{{ $key }}'" @if ($key !== 'ltv') x-cloak @endif>
                    <svg class="ltv-chart" style="width: {{ $chartWidth }}px" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" role="img" aria-label="Динамика показателя {{ $dataset['label'] }} по месяцам">
                        @for ($tick = 0; $tick <= 4; $tick++)
                            @php
                                $tickY = $chartTop + $chartPlotHeight - (($chartPlotHeight / 4) * $tick);
                                $tickValue = ($dataset['chart']['maximum'] / 4) * $tick;
                            @endphp
                            <line class="ltv-chart__grid" x1="{{ $chartLeft }}" y1="{{ $tickY }}" x2="{{ $chartLeft + $chartPlotWidth }}" y2="{{ $tickY }}" />
                            <text class="ltv-chart__axis-label" x="{{ $chartLeft - 10 }}" y="{{ $tickY + 4 }}" text-anchor="end">{{ $number($tickValue, 0) }} BYN</text>
                        @endfor

                        @if ($dataset['chart']['points']->isNotEmpty())
                            <polygon class="ltv-chart__area" points="{{ $dataset['chart']['area'] }}" />
                            <polyline class="ltv-chart__line" points="{{ $dataset['chart']['polyline'] }}" />

                            @foreach ($dataset['chart']['points'] as $point)
                                <circle class="ltv-chart__point" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="5">
                                    <title>{{ $point['row']['label'] }}: {{ $money($point['row'][$dataset['metric']]) }}</title>
                                </circle>
                                <text class="ltv-chart__axis-label" x="{{ $point['x'] }}" y="{{ $chartTop + $chartPlotHeight + 24 }}" text-anchor="middle">{{ \Illuminate\Support\Carbon::createFromFormat('!Y-m', $point['row']['month'])->locale('ru')->translatedFormat('M Y') }}</text>
                            @endforeach
                        @endif
                    </svg>
                </div>
            @endforeach
        </section>
    </div>
</x-filament-panels::page>
