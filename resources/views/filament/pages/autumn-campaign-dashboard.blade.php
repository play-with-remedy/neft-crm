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

        .seasonal-overview {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
            color: #111827;
        }

        .seasonal-overview__header {
            display: grid;
            gap: 14px;
            width: 100%;
            max-width: 360px;
            min-width: 0;
        }

        .seasonal-overview__dates {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6b7280;
            font-size: 13px;
            line-height: 1.5;
        }

        .seasonal-overview__dates svg {
            width: 18px;
            height: 18px;
            flex: 0 0 auto;
            color: #9ca3af;
        }

        .seasonal-overview__rules {
            display: flex;
            align-items: center;
        }

        .seasonal-overview__reward {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .seasonal-overview__gift {
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            flex: 0 0 auto;
            border-radius: 10px;
            background: #fffbeb;
            color: #b45309;
        }

        .seasonal-overview__gift svg {
            width: 20px;
            height: 20px;
        }

        .seasonal-overview__title {
            font-size: 14px;
            font-weight: 600;
            line-height: 1.5;
        }

        .seasonal-overview__subtitle {
            margin-top: 2px;
            color: #6b7280;
            font-size: 13px;
            line-height: 1.5;
        }

        .seasonal-overview__note {
            color: #6b7280;
            font-size: 12px;
            line-height: 1.6;
        }

        .dark .seasonal-overview {
            border-color: rgba(255, 255, 255, 0.10);
            background: #18181b;
            color: #f4f4f5;
        }

        .dark :is(.seasonal-overview__dates, .seasonal-overview__subtitle, .seasonal-overview__note) {
            color: #a1a1aa;
        }

        .dark .seasonal-overview__gift {
            background: rgba(245, 158, 11, 0.10);
            color: #fbbf24;
        }

        @media (max-width: 640px) {
            .seasonal-overview {
                gap: 16px;
                padding: 16px;
            }

            .seasonal-overview__header,
            .seasonal-overview__header > * {
                width: 100%;
                max-width: none;
            }
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
        <section class="seasonal-overview" aria-label="Сезон и условия акции">
            <div class="seasonal-overview__header">
                @include('filament.partials.seasonal-campaign-select')

                @if ($summary['campaign'])
                    <div class="seasonal-overview__dates">
                        <x-heroicon-o-calendar-days />
                        <span>{{ $summary['campaign']->starts_at->format('d.m.Y') }} — {{ $summary['campaign']->ends_at->format('d.m.Y') }}</span>
                    </div>
                @endif
            </div>

            @if ($summary['campaign'])
                <div class="seasonal-overview__rules">
                    <div class="seasonal-overview__reward">
                        <span class="seasonal-overview__gift" aria-hidden="true"><x-heroicon-o-gift /></span>
                        <div>
                            <div class="seasonal-overview__title">Бесплатный вечер в подарок</div>
                            <div class="seasonal-overview__subtitle">После {{ $summary['campaign']->required_visits }} зачётных посещений</div>
                        </div>
                    </div>
                </div>
            @else
                <p class="seasonal-overview__note">Создайте кампанию, чтобы начать учитывать посещения.</p>
            @endif
        </section>

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
