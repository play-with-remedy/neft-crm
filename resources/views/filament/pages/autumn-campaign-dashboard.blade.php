<x-filament-panels::page>
    @php
        $summary = $this->getSummary();

        $items = [
            ['key' => 'players', 'label' => 'Участников'],
            ['key' => 'active', 'label' => 'В процессе'],
            ['key' => 'rewards', 'label' => 'Награда доступна'],
            ['key' => 'completed', 'label' => 'Завершено'],
            ['key' => 'expired', 'label' => 'Просрочено'],
        ];
    @endphp

    <style>
        .autumn-summary {
            margin-bottom: 24px;
        }

        .autumn-summary__header {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
        }

        .autumn-summary__period {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 36px;
            padding: 7px 12px;
            border: 1px solid rgba(251, 146, 60, 0.24);
            border-radius: 10px;
            background: rgba(251, 146, 60, 0.08);
            color: #d4d4d8;
            font-size: 13px;
            font-weight: 600;
        }

        .autumn-summary__period svg {
            width: 18px;
            height: 18px;
            flex: 0 0 auto;
            color: #fb923c;
        }

        .autumn-summary__period-label {
            color: #a1a1aa;
            font-weight: 500;
        }

        .autumn-summary__empty-note {
            color: #a1a1aa;
            font-size: 13px;
        }

        .autumn-summary__grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }

        .autumn-summary__card {
            padding: 14px 16px;
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 14px;
            background: #18181b;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
        }

        .autumn-summary__label {
            margin-bottom: 8px;
            color: #a1a1aa;
            font-size: 12px;
            font-weight: 500;
        }

        .autumn-summary__value {
            color: #ffffff;
            font-size: 20px;
            font-weight: 700;
            line-height: 1.2;
            white-space: nowrap;
        }

        .autumn-summary__card--active .autumn-summary__value {
            color: #38bdf8;
        }

        .autumn-summary__card--rewards .autumn-summary__value,
        .autumn-summary__card--completed .autumn-summary__value {
            color: #22c55e;
        }

        .autumn-summary__card--expired .autumn-summary__value {
            color: #f97316;
        }
    </style>

    <div class="autumn-summary">
        <div class="autumn-summary__header">
            @if ($summary['campaign'])
                <div class="autumn-summary__period">
                    <x-heroicon-o-calendar-days />
                    <span class="autumn-summary__period-label">Период</span>
                    <span>{{ $summary['campaign']->starts_at->format('d.m.Y') }} — {{ $summary['campaign']->ends_at->format('d.m.Y') }}</span>
                </div>
            @else
                <div class="autumn-summary__empty-note">
                    Создайте кампанию, чтобы начать учитывать посещения
                </div>
            @endif
        </div>

        <div class="autumn-summary__grid">
            @foreach ($items as $item)
                <div class="autumn-summary__card autumn-summary__card--{{ $item['key'] }}">
                    <div class="autumn-summary__label">
                        {{ $item['label'] }}
                    </div>

                    <div class="autumn-summary__value">
                        {{ $summary[$item['key']] }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
