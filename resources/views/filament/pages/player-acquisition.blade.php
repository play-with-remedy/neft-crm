<x-filament-panels::page>
    <style>
        .acquisition-period {
            width: min(100%, 620px);
            margin-bottom: 22px;
        }

        .acquisition-label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 600;
        }

        .acquisition-period-range {
            display: grid;
            grid-template-columns: minmax(150px, 1fr) minmax(150px, 1fr) auto;
            align-items: end;
            gap: 10px;
        }

        .acquisition-period-field label {
            display: block;
            margin-bottom: 5px;
            color: rgb(107, 114, 128);
            font-size: 12px;
        }

        .acquisition-period-input {
            width: 100%;
            border: 1px solid rgb(209, 213, 219);
            border-radius: 8px;
            background: white;
            padding: 9px 12px;
            color: rgb(17, 24, 39);
            cursor: pointer;
        }

        .acquisition-period-apply {
            border-radius: 7px;
            background: rgb(245, 158, 11);
            padding: 9px 14px;
            color: white;
            font-size: 13px;
            font-weight: 600;
        }

        .acquisition-period-apply:hover {
            background: rgb(217, 119, 6);
        }

        .acquisition-period-help,
        .acquisition-table-empty {
            color: rgb(107, 114, 128);
            font-size: 12px;
        }

        .acquisition-period-help {
            margin-top: 6px;
        }

        .acquisition-period-error {
            margin-top: 6px;
            color: rgb(220, 38, 38);
            font-size: 13px;
        }

        .acquisition-tables {
            display: grid;
            gap: 18px;
        }

        .acquisition-table-card {
            overflow: hidden;
            border: 1px solid rgb(229, 231, 235);
            border-radius: 12px;
            background: white;
        }

        .acquisition-table-title {
            padding: 16px 18px;
            border-bottom: 1px solid rgb(229, 231, 235);
            font-size: 16px;
            font-weight: 700;
        }

        .acquisition-table-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 12px 18px;
            border-bottom: 1px solid rgb(229, 231, 235);
        }

        .acquisition-table-heading .acquisition-table-title {
            padding: 0;
            border: 0;
        }

        .acquisition-page-size select {
            border: 1px solid rgb(209, 213, 219);
            border-radius: 7px;
            background: white;
            padding: 6px 9px;
            font-size: 13px;
        }

        .acquisition-pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            padding: 14px 18px;
            border-top: 1px solid rgb(229, 231, 235);
            font-size: 13px;
        }

        .acquisition-pagination button {
            border: 1px solid rgb(209, 213, 219);
            border-radius: 7px;
            padding: 6px 11px;
        }

        .acquisition-pagination button:disabled {
            cursor: not-allowed;
            opacity: 0.45;
        }

        .acquisition-table-title-note {
            color: rgb(107, 114, 128);
            font-size: 13px;
            font-weight: 400;
        }

        .acquisition-table {
            width: 100%;
            border-collapse: collapse;
        }

        .acquisition-table-wrap {
            overflow-x: auto;
        }

        .acquisition-table th,
        .acquisition-table td {
            padding: 13px 16px;
            border-bottom: 1px solid rgb(229, 231, 235);
            text-align: center;
            white-space: nowrap;
        }

        .acquisition-table th {
            background: rgb(249, 250, 251);
            color: rgb(75, 85, 99);
            font-size: 12px;
            font-weight: 700;
        }

        .acquisition-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .acquisition-summary-row td {
            border-top: 2px solid rgb(209, 213, 219);
            background: rgb(249, 250, 251);
            color: rgb(180, 83, 9);
            font-weight: 700;
        }

        .acquisition-table td:first-child,
        .acquisition-table th:first-child {
            text-align: left;
        }

        .acquisition-table-empty {
            padding: 28px 18px !important;
            text-align: center;
        }

        .acquisition-source {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            font-weight: 600;
        }

        .acquisition-source-icon {
            width: 21px;
            height: 21px;
            flex: 0 0 auto;
        }

        .acquisition-source-icon--instagram { color: rgb(219, 39, 119); }
        .acquisition-source-icon--telegram { color: rgb(14, 165, 233); }
        .acquisition-source-icon--youtube { color: rgb(239, 68, 68); }
        .acquisition-source-icon--search { color: rgb(59, 130, 246); }
        .acquisition-source-icon--referral { color: rgb(16, 185, 129); }
        .acquisition-source-icon--other { color: rgb(107, 114, 128); }

        .acquisition-expense-button {
            color: rgb(217, 119, 6);
            font-weight: 600;
            text-decoration: underline;
            text-decoration-color: rgb(245 158 11 / 45%);
            text-underline-offset: 3px;
        }

        .acquisition-expense-button:hover {
            color: rgb(180, 83, 9);
        }

        .acquisition-expense-editor {
            display: grid;
            gap: 12px;
        }

        .acquisition-expense-row {
            display: grid;
            grid-template-columns: minmax(130px, 1fr) minmax(130px, 180px);
            align-items: center;
            gap: 14px;
        }

        .acquisition-expense-row label {
            font-weight: 600;
        }

        .acquisition-expense-input {
            width: 100%;
            border: 1px solid rgb(209, 213, 219);
            border-radius: 8px;
            background: white;
            padding: 8px 10px;
            text-align: right;
        }

        .acquisition-expense-error {
            grid-column: 2;
            margin-top: -8px;
            color: rgb(220, 38, 38);
            font-size: 12px;
        }

        .acquisition-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .acquisition-modal-cancel,
        .acquisition-modal-save {
            border-radius: 8px;
            padding: 9px 14px;
            font-size: 13px;
            font-weight: 600;
        }

        .acquisition-modal-cancel {
            background: rgb(243, 244, 246);
            color: rgb(55, 65, 81);
        }

        .acquisition-modal-save {
            background: rgb(245, 158, 11);
            color: white;
        }

        .dark .acquisition-period-input,
        .dark .acquisition-table-card {
            border-color: rgb(75, 85, 99);
            background: rgb(17, 24, 39);
            color: rgb(243, 244, 246);
            color-scheme: dark;
        }

        .dark .acquisition-table-title {
            border-color: rgb(55, 65, 81);
        }

        .dark .acquisition-table-heading,
        .dark .acquisition-pagination {
            border-color: rgb(55, 65, 81);
        }

        .dark .acquisition-page-size select {
            border-color: rgb(75, 85, 99);
            background: rgb(17, 24, 39);
            color: rgb(243, 244, 246);
        }

        .dark .acquisition-table-title-note {
            color: rgb(156, 163, 175);
        }

        .dark .acquisition-table th {
            background: rgb(31, 41, 55);
            color: rgb(209, 213, 219);
        }

        .dark .acquisition-table th,
        .dark .acquisition-table td {
            border-color: rgb(55, 65, 81);
        }

        .dark .acquisition-summary-row td {
            background: rgb(31, 41, 55);
            color: rgb(251, 191, 36);
        }

        .dark .acquisition-expense-input {
            border-color: rgb(75, 85, 99);
            background: rgb(17, 24, 39);
            color: rgb(243, 244, 246);
        }

        .dark .acquisition-modal-cancel {
            background: rgb(55, 65, 81);
            color: rgb(229, 231, 235);
        }

        @media (max-width: 640px) {
            .acquisition-period,
            .acquisition-period-range {
                width: 100%;
            }

            .acquisition-period-range {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="acquisition-period">
        <span class="acquisition-label">Период</span>

        <div class="acquisition-period-range">
            <div class="acquisition-period-field">
                <label for="acquisition-period-from">С месяца</label>
                <input
                    id="acquisition-period-from"
                    type="month"
                    wire:model="pendingPeriodFrom"
                    x-on:click="$el.showPicker?.()"
                    class="acquisition-period-input"
                >
            </div>

            <div class="acquisition-period-field">
                <label for="acquisition-period-until">По месяц</label>
                <input
                    id="acquisition-period-until"
                    type="month"
                    wire:model="pendingPeriodUntil"
                    x-on:click="$el.showPicker?.()"
                    class="acquisition-period-input"
                >
            </div>

            <button
                type="button"
                class="acquisition-period-apply"
                wire:click="applyPeriodRange"
            >
                Применить
            </button>
        </div>

        <div class="acquisition-period-help">
            Для одного месяца укажите одинаковый месяц в обоих полях. Максимальный период — 12 месяцев.
        </div>

        @error('periodRange')
            <div class="acquisition-period-error">{{ $message }}</div>
        @enderror
    </div>

    <div
        class="acquisition-tables"
        wire:loading.class="opacity-60"
        wire:target="applyPeriodRange"
    >
        @php
            $monthlySummary = $this->getMonthlyDynamicsSummary();
            $sourcesSummary = $this->getSourcesSummary();
        @endphp

        <section
            class="acquisition-table-card"
            x-data="{
                page: 1,
                perPage: '6',
                total: {{ count($monthlyDynamics) }},
                get pages() {
                    return this.perPage === 'all'
                        ? 1
                        : Math.max(1, Math.ceil(this.total / Number(this.perPage)));
                },
                visible(index) {
                    if (this.perPage === 'all') return true;

                    const size = Number(this.perPage);
                    return index >= ((this.page - 1) * size) && index < (this.page * size);
                },
            }"
        >
            <div class="acquisition-table-heading">
                <h2 class="acquisition-table-title">Динамика по месяцам</h2>
                <label class="acquisition-page-size">
                    <select x-model="perPage" x-on:change="page = 1" aria-label="Количество отображаемых месяцев">
                        <option value="3">3 месяца</option>
                        <option value="6">6 месяцев</option>
                        <option value="12">12 месяцев</option>
                        <option value="all">Весь период</option>
                    </select>
                </label>
            </div>
            <div class="acquisition-table-wrap">
                <table class="acquisition-table">
                    <thead>
                        <tr>
                            <th scope="col">Месяц первого визита</th>
                            <th scope="col">Новых игроков</th>
                            <th scope="col">Расходы на рекламу</th>
                            <th scope="col">CAC</th>
                            <th scope="col">Средний LTV</th>
                            <th scope="col">Средний чек</th>
                            <th scope="col">Средняя частота</th>
                            <th scope="col">Конверсия в постоянных</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($monthlyDynamics as $monthIndex => $month)
                            <tr x-show="visible({{ $monthIndex }})">
                                <td>{{ $month['label'] }}</td>
                                <td>{{ $month['new_players_count'] }}</td>
                                <td>{{ number_format($month['advertising_expenses'], 2, ',', ' ') }} BYN</td>
                                <td>{{ number_format($month['cac'], 2, ',', ' ') }} BYN</td>
                                <td>{{ number_format($month['average_ltv'], 2, ',', ' ') }} BYN</td>
                                <td>{{ number_format($month['average_check'], 2, ',', ' ') }} BYN</td>
                                <td>{{ number_format($month['average_frequency'], 1, ',', ' ') }}</td>
                                <td>{{ number_format($month['regular_conversion'], 1, ',', ' ') }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="acquisition-table-empty">Пока нет игроков с датой первого посещения.</td>
                            </tr>
                        @endforelse

                        @if (count($monthlyDynamics))
                            <tr class="acquisition-summary-row">
                                <td>Итого / Среднее</td>
                                <td>{{ $monthlySummary['new_players_count'] }}</td>
                                <td>{{ number_format($monthlySummary['advertising_expenses'], 2, ',', ' ') }} BYN</td>
                                <td>{{ number_format($monthlySummary['cac'], 2, ',', ' ') }} BYN</td>
                                <td>{{ number_format($monthlySummary['average_ltv'], 2, ',', ' ') }} BYN</td>
                                <td>{{ number_format($monthlySummary['average_check'], 2, ',', ' ') }} BYN</td>
                                <td>{{ number_format($monthlySummary['average_frequency'], 1, ',', ' ') }}</td>
                                <td>{{ number_format($monthlySummary['regular_conversion'], 1, ',', ' ') }}%</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="acquisition-pagination" x-show="pages > 1" x-cloak>
                <button type="button" x-on:click="page--" x-bind:disabled="page === 1">Назад</button>
                <span>Страница <span x-text="page"></span> из <span x-text="pages"></span></span>
                <button type="button" x-on:click="page++" x-bind:disabled="page === pages">Далее</button>
            </div>
        </section>

        <section class="acquisition-table-card">
            <h2 class="acquisition-table-title">
                Источники привлечения
                <span class="acquisition-table-title-note">(за выбранный период)</span>
            </h2>
            <div class="acquisition-table-wrap">
                <table class="acquisition-table">
                    <thead>
                        <tr>
                            <th scope="col">Источник</th>
                            <th scope="col">Новых игроков</th>
                            <th scope="col">% от всех новых</th>
                            <th scope="col">Расходы на рекламу</th>
                            <th scope="col">CAC</th>
                            <th scope="col">Средний LTV</th>
                            <th scope="col">Конверсия в постоянных</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sources as $source)
                            <tr wire:key="acquisition-source-{{ $source['id'] }}">
                                <td>
                                    <span class="acquisition-source">
                                        @switch($source['name'])
                                            @case('Instagram')
                                                <svg class="acquisition-source-icon acquisition-source-icon--instagram" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <rect x="3" y="3" width="18" height="18" rx="5" />
                                                    <circle cx="12" cy="12" r="4" />
                                                    <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none" />
                                                </svg>
                                                @break

                                            @case('Telegram')
                                                <svg class="acquisition-source-icon acquisition-source-icon--telegram" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="m3 11 18-8-4.5 18-5.2-6.1L8 18v-5l9-6-11 5-3-1Z" />
                                                </svg>
                                                @break

                                            @case('YouTube')
                                                <svg class="acquisition-source-icon acquisition-source-icon--youtube" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true">
                                                    <rect x="2.5" y="5.5" width="19" height="13" rx="4" />
                                                    <path d="m10 9 5 3-5 3V9Z" />
                                                </svg>
                                                @break

                                            @case('Поиск в Google/Яндекс')
                                                <svg class="acquisition-source-icon acquisition-source-icon--search" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                                                    <circle cx="10.5" cy="10.5" r="6.5" />
                                                    <path d="m15.5 15.5 5 5" />
                                                </svg>
                                                @break

                                            @case('Рекомендации знакомых')
                                                <svg class="acquisition-source-icon acquisition-source-icon--referral" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                                                    <circle cx="9" cy="8" r="3" />
                                                    <circle cx="17" cy="9" r="2.5" />
                                                    <path d="M3.5 19c.4-4 2.2-6 5.5-6s5.1 2 5.5 6" />
                                                    <path d="M14 14c3.8-.6 5.8 1 6.5 4" />
                                                </svg>
                                                @break

                                            @default
                                                <svg class="acquisition-source-icon acquisition-source-icon--other" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                    <circle cx="5" cy="12" r="1.7" />
                                                    <circle cx="12" cy="12" r="1.7" />
                                                    <circle cx="19" cy="12" r="1.7" />
                                                </svg>
                                        @endswitch

                                        <span>{{ $source['name'] }}</span>
                                    </span>
                                </td>
                                <td>{{ $source['new_players_count'] }}</td>
                                <td>{{ number_format($source['new_players_percentage'], 1, ',', ' ') }}%</td>
                                <td>
                                    @if ($source['id'] > 0)
                                        <button
                                            type="button"
                                            class="acquisition-expense-button"
                                            wire:click="openAdvertisingExpenses({{ $source['id'] }})"
                                        >
                                            {{ number_format($source['advertising_expenses'], 2, ',', ' ') }} BYN
                                        </button>
                                    @else
                                        {{ number_format($source['advertising_expenses'], 2, ',', ' ') }} BYN
                                    @endif
                                </td>
                                <td>{{ number_format($source['cac'], 2, ',', ' ') }} BYN</td>
                                <td>{{ number_format($source['average_ltv'], 2, ',', ' ') }} BYN</td>
                                <td>{{ number_format($source['regular_conversion'], 1, ',', ' ') }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="acquisition-table-empty">Источники пока не добавлены.</td>
                            </tr>
                        @endforelse

                        @if (count($sources))
                            <tr class="acquisition-summary-row">
                                <td>Итого / Среднее</td>
                                <td>{{ $sourcesSummary['new_players_count'] }}</td>
                                <td>{{ number_format($sourcesSummary['new_players_percentage'], 1, ',', ' ') }}%</td>
                                <td>{{ number_format($sourcesSummary['advertising_expenses'], 2, ',', ' ') }} BYN</td>
                                <td>{{ number_format($sourcesSummary['cac'], 2, ',', ' ') }} BYN</td>
                                <td>{{ number_format($sourcesSummary['average_ltv'], 2, ',', ' ') }} BYN</td>
                                <td>{{ number_format($sourcesSummary['regular_conversion'], 1, ',', ' ') }}%</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <x-filament::modal
        id="source-advertising-expenses"
        width="lg"
        :heading="'Расходы на рекламу — ' . $editingSourceName"
    >
        <div class="acquisition-expense-editor">
            @foreach ($advertisingExpenseMonths as $month => $label)
                <div class="acquisition-expense-row" wire:key="advertising-expense-{{ $month }}">
                    <label for="advertising-expense-{{ $month }}">{{ $label }}</label>
                    <input
                        id="advertising-expense-{{ $month }}"
                        type="number"
                        min="0"
                        step="0.01"
                        wire:model="advertisingExpenses.{{ $month }}"
                        class="acquisition-expense-input"
                    >

                    @error("advertisingExpenses.{$month}")
                        <div class="acquisition-expense-error">{{ $message }}</div>
                    @enderror
                </div>
            @endforeach
        </div>

        <div class="acquisition-modal-actions">
            <button
                type="button"
                class="acquisition-modal-cancel"
                x-on:click="$dispatch('close-modal', { id: 'source-advertising-expenses' })"
            >
                Отмена
            </button>
            <button
                type="button"
                class="acquisition-modal-save"
                wire:click="saveAdvertisingExpenses"
            >
                Сохранить
            </button>
        </div>
    </x-filament::modal>
</x-filament-panels::page>
