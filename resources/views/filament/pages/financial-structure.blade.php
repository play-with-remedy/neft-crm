<x-filament-panels::page>
    <div class="financial-toolbar">
        <div class="financial-period">
            <label
                for="financial-period"
                class="financial-label"
            >
                Месяц
            </label>

            <input
                id="financial-period"
                type="month"
                wire:model.live="period"
                @disabled($isEditing)
                class="financial-period-input"
            >

            @error('period')
                <div class="financial-error">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="financial-actions">
            @if ($isEditing)
                <x-filament::button
                    type="button"
                    color="gray"
                    wire:click="cancelEditing"
                >
                    Отмена
                </x-filament::button>

                <x-filament::button
                    type="button"
                    icon="heroicon-o-check"
                    wire:click="save"
                >
                    Сохранить
                </x-filament::button>
            @else
                <x-filament::button
                    type="button"
                    icon="heroicon-o-pencil-square"
                    wire:click="startEditing"
                >
                    Редактировать месяц
                </x-filament::button>
            @endif
        </div>
    </div>

    <div
        wire:loading
        wire:target="period,startEditing,cancelEditing,save"
        class="financial-loading"
    >
        Загрузка…
    </div>

    <div
        wire:loading.remove
        wire:target="period,startEditing,cancelEditing,save"
        class="financial-model"
    >
        @php
            $clubRevenue = $this->clubRevenue();
            $corporateRevenueAmount = (float) ($corporateRevenue ?: 0);
            $totalRevenue = $clubRevenue + $corporateRevenueAmount;
            $totalExpenses = (float) $categories->sum(
                fn ($category) => $this->categoryTotal($category)
            );
        @endphp

        <div class="financial-table-header">
            <div>Статья</div>
            <div>Сумма</div>
            <div>Проценты</div>
            <div>Детали</div>
        </div>

        <div class="financial-row financial-row--summary">
            <div class="financial-name">
                Выручка клуба
            </div>

            <div class="financial-amount">
                {{ $this->formatAmount($clubRevenue) }}
            </div>

            <div class="financial-percentage">
                {{
                    $this->formatPercentage(
                        $clubRevenue,
                        $totalRevenue
                    )
                }}
            </div>

            <div class="financial-details">
                Рассчитано по оплатам участников
            </div>
        </div>

        <div class="financial-row financial-row--summary">
            <div class="financial-name">
                Выручка корпоративов
            </div>

            <div>
                @if ($isEditing)
                    <input
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                        wire:model.defer="corporateRevenue"
                        class="financial-amount-input"
                    >

                    @error('corporateRevenue')
                        <div class="financial-error">
                            {{ $message }}
                        </div>
                    @enderror
                @else
                    <div class="financial-amount">
                        {{ $this->formatAmount($corporateRevenue) }}
                    </div>
                @endif
            </div>

            <div class="financial-percentage">
                {{
                    $this->formatPercentage(
                        $corporateRevenueAmount,
                        $totalRevenue
                    )
                }}
            </div>

            <div class="financial-details">
                Вводится вручную
            </div>
        </div>

        <div class="financial-row financial-row--summary">
            <div class="financial-name">
                Общая выручка
            </div>

            <div class="financial-amount">
                {{
                    $this->formatAmount($totalRevenue)
                }}
            </div>

            <div class="financial-percentage">
                100,00 %
            </div>

            <div class="financial-details">
                Выручка клуба + выручка корпоративов
            </div>
        </div>

        <div class="financial-row financial-row--summary">
            <div class="financial-name">
                Общие расходы
            </div>

            <div class="financial-amount">
                {{ $this->formatAmount($totalExpenses) }}
            </div>

            <div class="financial-percentage">
                {{
                    $this->formatPercentage(
                        $totalExpenses,
                        $totalRevenue
                    )
                }}
            </div>

            <div class="financial-details">
                Сумма категорий первого уровня
            </div>
        </div>

        @forelse ($categories as $category)
            @include(
                'filament.pages.partials.financial-category-node',
                [
                    'category' => $category,
                    'level' => 1,
                    'totalRevenue' => $totalRevenue,
                ]
            )
        @empty
            <div class="financial-empty">
                Финансовые статьи пока не созданы.
            </div>
        @endforelse

        <div class="financial-row financial-row--summary">
            <div class="financial-name">
                Чистая прибыль
            </div>

            <div class="financial-amount">
                {{
                    $this->formatAmount(
                        $totalRevenue - $totalExpenses
                    )
                }}
            </div>

            <div class="financial-percentage">
                {{
                    $this->formatPercentage(
                        $totalRevenue - $totalExpenses,
                        $totalRevenue
                    )
                }}
            </div>

            <div class="financial-details">
                Общая выручка − общие расходы
            </div>
        </div>
    </div>

    <style>
        .financial-toolbar {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
        }

        .financial-actions {
            display: flex;
            gap: 10px;
        }

        .financial-period {
            width: 240px;
        }

        .financial-label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 600;
        }

        .financial-period-input,
        .financial-amount-input,
        .financial-details-input {
            width: 100%;
            border: 1px solid rgb(209, 213, 219);
            border-radius: 8px;
            background: white;
            padding: 9px 12px;
            color: rgb(17, 24, 39);
        }

        .financial-period-input {
            cursor: pointer;
        }

        .financial-period-input::-webkit-calendar-picker-indicator {
            cursor: pointer;
        }

        .financial-period-input:disabled {
            cursor: not-allowed;
            opacity: 0.65;
        }

        .dark .financial-period-input,
        .dark .financial-amount-input,
        .dark .financial-details-input {
            border-color: rgb(75, 85, 99);
            background: rgb(17, 24, 39);
            color: rgb(243, 244, 246);
        }

        .financial-model {
            overflow: hidden;
            border: 1px solid rgb(229, 231, 235);
            border-radius: 12px;
            background: white;
        }

        .dark .financial-model {
            border-color: rgb(55, 65, 81);
            background: rgb(17, 24, 39);
        }

        .financial-table-header,
        .financial-row {
            display: grid;
            grid-template-columns:
                minmax(250px, 1.4fr)
                minmax(140px, 220px)
                minmax(100px, 140px)
                minmax(220px, 1fr);
            align-items: center;
            gap: 16px;
        }

        .financial-table-header {
            padding: 12px 16px;
            border-bottom: 1px solid rgb(229, 231, 235);
            background: rgb(249, 250, 251);
            color: rgb(107, 114, 128);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .dark .financial-table-header {
            border-color: rgb(55, 65, 81);
            background: rgb(31, 41, 55);
            color: rgb(209, 213, 219);
        }

        .financial-row {
            min-height: 54px;
            padding: 9px 16px;
            border-bottom: 1px solid rgb(243, 244, 246);
        }

        .dark .financial-row {
            border-color: rgb(31, 41, 55);
        }

        .financial-row--root {
            background: rgb(239, 246, 255);
            border-left: 4px solid rgb(59, 130, 246);
            color: rgb(30, 64, 175);
            font-weight: 700;
        }

        .financial-row--summary {
            font-weight: 700;
        }

        .dark .financial-row--root {
            background: rgb(51, 65, 85);
            border-left-color: rgb(96, 165, 250);
            color: rgb(255, 255, 255);
        }

        [x-cloak] {
            display: none !important;
        }

        .financial-row--parent {
            font-weight: 600;
        }

        .financial-name {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .financial-toggle {
            display: inline-flex;
            flex: 0 0 24px;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border: 0;
            border-radius: 6px;
            background: transparent;
            color: inherit;
            cursor: pointer;
        }

        .financial-toggle:hover {
            background: rgb(229, 231, 235);
        }

        .dark .financial-toggle:hover {
            background: rgb(55, 65, 81);
        }

        .financial-toggle-icon {
            display: block;
            font-size: 22px;
            line-height: 1;
            transform: rotate(0deg);
            transition: transform 150ms ease;
        }

        .financial-toggle-icon--open {
            transform: rotate(90deg);
        }

        .financial-name--level-1 {
            padding-left: 0;
        }

        .financial-name--level-2 {
            padding-left: 24px;
        }

        .financial-name--level-3 {
            padding-left: 48px;
        }

        .financial-amount {
            font-variant-numeric: tabular-nums;
            font-weight: 600;
            text-align: left;
        }

        .financial-percentage {
            font-variant-numeric: tabular-nums;
            text-align: left;
        }

        .financial-details {
            color: rgb(107, 114, 128);
            font-size: 14px;
        }

        .financial-error {
            margin-top: 5px;
            color: rgb(220, 38, 38);
            font-size: 12px;
        }

        .financial-loading,
        .financial-empty {
            padding: 24px;
            text-align: center;
            color: rgb(107, 114, 128);
        }

        @media (max-width: 900px) {
            .financial-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .financial-period {
                width: 100%;
            }

            .financial-actions {
                justify-content: flex-end;
            }

            .financial-table-header {
                display: none;
            }

            .financial-row {
                grid-template-columns: 1fr;
            }

            .financial-amount {
                text-align: left;
            }

            .financial-percentage {
                text-align: left;
            }
        }
    </style>
</x-filament-panels::page>
