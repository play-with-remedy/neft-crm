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
            margin-bottom: 12px;
        }

        .autumn-summary__title {
            margin: 0;
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
        }

        .autumn-summary__subtitle {
            margin-top: 4px;
            color: #9ca3af;
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
            <h2 class="autumn-summary__title">
                {{ $summary['campaign']?->name ?? 'Кампания не создана' }}
            </h2>

            <div class="autumn-summary__subtitle">
                @if ($summary['campaign'])
                    Период: {{ $summary['campaign']->starts_at->format('d.m.Y') }}–{{ $summary['campaign']->ends_at->format('d.m.Y') }}
                @else
                    Создайте кампанию, чтобы начать учитывать посещения
                @endif
            </div>
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
