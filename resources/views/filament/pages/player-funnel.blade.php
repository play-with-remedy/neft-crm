<x-filament-panels::page>
    <style>
        .funnel-toolbar {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
        }

        .funnel-period {
            position: relative;
            width: min(100%, 620px);
        }

        .funnel-label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 600;
        }

        .funnel-period-input {
            width: 100%;
            border: 1px solid rgb(209, 213, 219);
            border-radius: 8px;
            background: white;
            padding: 9px 12px;
            color: rgb(17, 24, 39);
            cursor: pointer;
        }

        .funnel-period-input::marker {
            color: rgb(107, 114, 128);
        }

        .funnel-period-options {
            position: absolute;
            z-index: 30;
            top: calc(100% + 6px);
            left: 0;
            width: 100%;
            max-height: 320px;
            overflow-y: auto;
            border: 1px solid rgb(209, 213, 219);
            border-radius: 8px;
            background: white;
            padding: 8px;
            box-shadow: 0 12px 28px rgb(0 0 0 / 14%);
        }

        .funnel-period-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 8px;
            border-radius: 6px;
            cursor: pointer;
        }

        .funnel-period-option:hover {
            background: rgb(243, 244, 246);
        }

        .funnel-period-option input {
            width: 16px;
            height: 16px;
        }

        .funnel-period-limit {
            padding: 6px 8px 2px;
            color: rgb(107, 114, 128);
            font-size: 12px;
        }

        .funnel-period-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 8px;
            padding: 8px 8px 2px;
            border-top: 1px solid rgb(229, 231, 235);
        }

        .funnel-period-apply {
            border-radius: 7px;
            background: rgb(245, 158, 11);
            padding: 7px 14px;
            color: white;
            font-size: 13px;
            font-weight: 600;
        }

        .funnel-period-apply:hover {
            background: rgb(217, 119, 6);
        }

        .funnel-period-apply:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .funnel-period-input::-webkit-calendar-picker-indicator {
            cursor: pointer;
        }

        .dark .funnel-period-input {
            border-color: rgb(75, 85, 99);
            background: rgb(17, 24, 39);
            color: rgb(243, 244, 246);
            color-scheme: dark;
        }

        .dark .funnel-period-options {
            border-color: rgb(75, 85, 99);
            background: rgb(17, 24, 39);
        }

        .dark .funnel-period-option:hover {
            background: rgb(31, 41, 55);
        }

        .dark .funnel-period-actions {
            border-color: rgb(55, 65, 81);
        }

        .funnel-period-range {
            display: grid;
            grid-template-columns: minmax(150px, 1fr) minmax(150px, 1fr) auto;
            align-items: end;
            gap: 10px;
        }

        .funnel-period-field label {
            display: block;
            margin-bottom: 5px;
            color: rgb(107, 114, 128);
            font-size: 12px;
        }

        .funnel-period-help {
            margin-top: 6px;
            color: rgb(107, 114, 128);
            font-size: 12px;
        }

        .funnel-period-error {
            margin-top: 6px;
            color: rgb(220, 38, 38);
            font-size: 13px;
        }

        .funnel-chart {
            width: min(100%, 560px);
            margin: 0;
            padding: 18px;
            border: 1px solid rgba(161, 161, 170, 0.3);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.72);
            font-family: ui-rounded, "Arial Rounded MT Bold", "Segoe UI", sans-serif;
        }

        .funnel-widgets {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 560px));
            gap: 20px;
            align-items: start;
        }

        .funnel-content {
            transition: opacity 160ms ease;
        }

        .funnel-content--loading {
            pointer-events: none;
            opacity: 0.58;
        }

        .dark .funnel-chart {
            background: rgba(24, 24, 27, 0.72);
        }

        .funnel-widget-title {
            margin: 0 0 30px;
            color: #18181b;
            font-size: 22px;
            font-weight: 800;
            line-height: 1.25;
        }

        .dark .funnel-widget-title {
            color: #f4f4f5;
        }

        .funnel-total {
            display: flex;
            align-items: center;
            height: 30px;
            text-align: left;
        }

        .funnel-total__label,
        .funnel-table__header {
            color: #71717a;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .funnel-total__label {
            white-space: nowrap;
        }

        .funnel-total__value {
            margin-left: 4px;
            color: #18181b;
            font-size: 19px;
            font-weight: 800;
        }

        .dark .funnel-total__value {
            color: #f4f4f5;
        }

        .dark .funnel-total__label,
        .dark .funnel-table__header,
        .dark .loss-table th {
            color: #f4f4f5;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .funnel-level {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 54px;
            margin-top: 7px;
            padding: 7px 18px;
            color: #18181b;
            text-align: center;
            text-shadow: none;
        }

        .dark .funnel-level {
            color: #fff;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.48);
        }

        .funnel-level::before {
            position: absolute;
            z-index: 0;
            inset: 0;
            clip-path: polygon(
                var(--top-left) 0,
                var(--top-right) 0,
                var(--bottom-right) 100%,
                var(--bottom-left) 100%
            );
            background: var(--funnel-color);
            opacity: 0.82;
            content: '';
        }

        .funnel-level > div {
            position: relative;
            z-index: 1;
        }

        .funnel-level__content {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            width: 100%;
            min-height: 40px;
            padding-left: calc(var(--icon-left) + 47px);
            text-align: left;
        }

        .funnel-level__icon {
            position: absolute;
            left: var(--icon-left);
            width: 38px;
            height: 38px;
            opacity: 0.8;
        }

        .funnel-level--new {
            --funnel-color: #e5e7eb;
        }

        .funnel-level--returned {
            --funnel-color: #bae6fd;
        }

        .funnel-level--interested {
            --funnel-color: #93c5fd;
        }

        .funnel-level--engaged {
            --funnel-color: #d8b4fe;
        }

        .funnel-level--contender {
            --funnel-color: #fed7aa;
        }

        .funnel-level--active {
            --funnel-color: #bbf7d0;
        }

        .funnel-level--regular {
            --funnel-color: #fef08a;
        }

        .dark .funnel-level--new {
            --funnel-color: #4b5563;
        }

        .dark .funnel-level--returned {
            --funnel-color: #0369a1;
        }

        .dark .funnel-level--interested {
            --funnel-color: #1d4ed8;
        }

        .dark .funnel-level--engaged {
            --funnel-color: #7e22ce;
        }

        .dark .funnel-level--contender {
            --funnel-color: #c2410c;
        }

        .dark .funnel-level--active {
            --funnel-color: #15803d;
        }

        .dark .funnel-level--regular {
            --funnel-color: #a16207;
        }

        .funnel-level__label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .funnel-level__metrics {
            margin-top: 2px;
            font-size: 13px;
            font-weight: 500;
            opacity: 0.92;
        }

        .funnel-visual {
            display: grid;
            grid-template-columns: minmax(270px, 333px) 190px;
            gap: 18px;
            align-items: start;
        }

        .funnel-table__header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            height: 30px;
            text-align: center;
            white-space: nowrap;
        }

        .funnel-table__row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            min-height: 54px;
            margin-top: 7px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
        }

        .funnel-table__value:first-child {
            font-size: 20px;
            font-weight: 800;
        }

        .loss-widget {
            width: min(100%, 560px);
            padding: 18px;
            border: 1px solid rgba(161, 161, 170, 0.3);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.72);
            font-family: ui-rounded, "Arial Rounded MT Bold", "Segoe UI", sans-serif;
        }

        .source-quality-widget {
            grid-column: 1 / -1;
            width: 100%;
            padding: 18px;
            border: 1px solid rgba(161, 161, 170, 0.3);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.72);
            font-family: ui-rounded, "Arial Rounded MT Bold", "Segoe UI", sans-serif;
        }

        .dark .source-quality-widget {
            background: rgba(24, 24, 27, 0.72);
        }

        .source-quality-table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
        }

        .source-quality-table-wrap {
            overflow-x: auto;
        }

        .source-quality-table th,
        .source-quality-table td {
            padding: 10px;
            text-align: center;
            white-space: nowrap;
        }

        .source-quality-table th:first-child,
        .source-quality-table td:first-child {
            padding-left: 0;
            text-align: left;
        }

        .funnel-stage-table th:first-child,
        .funnel-stage-table td:first-child {
            width: 220px;
            min-width: 220px;
            max-width: 220px;
        }

        .source-quality-table th {
            color: #71717a;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .source-quality-table td {
            color: #18181b;
            font-size: 14px;
            font-weight: 500;
        }

        .source-quality-table__metric {
            font-weight: 600;
            white-space: nowrap;
        }

        .transition-timing__icon {
            display: block;
            width: 28px;
            height: 28px;
            margin: 0 auto 7px;
            color: #71717a;
        }

        .transition-timing__heading {
            border-left: 4px solid rgba(255, 255, 255, 0.95);
            color: #18181b !important;
        }

        .transition-timing__heading--returned { background: #bae6fd; }
        .transition-timing__heading--new { background: #e5e7eb; }
        .transition-timing__heading--interested { background: #93c5fd; }
        .transition-timing__heading--engaged { background: #d8b4fe; }
        .transition-timing__heading--contender { background: #fed7aa; }
        .transition-timing__heading--active { background: #bbf7d0; }
        .transition-timing__heading--regular { background: #fef08a; }

        .dark .transition-timing__icon {
            color: #d4d4d8;
        }

        .dark .transition-timing__heading {
            border-left-color: rgba(24, 24, 27, 0.95);
            color: #fff !important;
        }

        .dark .transition-timing__heading--returned { background: #0369a1; }
        .dark .transition-timing__heading--new { background: #4b5563; }
        .dark .transition-timing__heading--interested { background: #1d4ed8; }
        .dark .transition-timing__heading--engaged { background: #7e22ce; }
        .dark .transition-timing__heading--contender { background: #c2410c; }
        .dark .transition-timing__heading--active { background: #15803d; }
        .dark .transition-timing__heading--regular { background: #a16207; }

        .stage-heading__content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
        }

        .stage-heading__content .transition-timing__icon,
        .stage-heading__icon {
            display: inline-block;
            flex: 0 0 auto;
            width: 22px;
            height: 22px;
            margin: 0;
            color: currentColor;
        }

        .source-quality-table__source {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .source-quality-table__source-icon {
            flex: 0 0 auto;
            width: 25px;
            height: 25px;
        }

        .source-quality-table__source-icon--instagram { color: #e1306c; }
        .source-quality-table__source-icon--telegram { color: #229ed9; }
        .source-quality-table__source-icon--youtube { color: #ff0000; }
        .source-quality-table__source-icon--search { color: #4285f4; }
        .source-quality-table__source-icon--referral { color: #16a34a; }
        .source-quality-table__source-icon--other { color: #a855f7; }

        .dark .source-quality-table__source-icon--referral {
            color: #4ade80;
        }

        .dynamics-toolbar,
        .dynamics-pagination {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dynamics-toolbar {
            justify-content: space-between;
            margin-bottom: 26px;
        }

        .dynamics-toolbar .funnel-widget-title {
            margin-bottom: 0;
        }

        .dynamics-page-size {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #71717a;
            font-size: 13px;
        }

        .dynamics-page-size select {
            border: 1px solid rgb(209, 213, 219);
            border-radius: 8px;
            background: white;
            padding: 6px 30px 6px 10px;
            color: #18181b;
            cursor: pointer;
        }

        .dynamics-pagination {
            justify-content: flex-end;
            margin-top: 14px;
        }

        .dynamics-pagination button {
            border: 1px solid rgba(161, 161, 170, 0.45);
            border-radius: 8px;
            padding: 6px 11px;
            color: #3f3f46;
            font-size: 13px;
            font-weight: 600;
        }

        .dynamics-pagination button:disabled {
            cursor: not-allowed;
            opacity: 0.45;
        }

        .dynamics-pagination__status {
            color: #71717a;
            font-size: 13px;
        }

        .dark .dynamics-page-size,
        .dark .dynamics-pagination__status {
            color: #a1a1aa;
        }

        .dark .dynamics-page-size select {
            border-color: rgb(75, 85, 99);
            background: rgb(17, 24, 39);
            color: rgb(243, 244, 246);
            color-scheme: dark;
        }

        .dark .dynamics-pagination button {
            color: #e4e4e7;
        }

        @media (max-width: 640px) {
            .funnel-widget-title {
                font-size: 19px;
            }

            .dynamics-toolbar {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        .dark .source-quality-table th {
            color: #f4f4f5;
        }

        .dark .source-quality-table td {
            color: #f4f4f5;
        }

        .dark .loss-widget {
            background: rgba(24, 24, 27, 0.72);
        }

        .loss-table {
            width: 100%;
            border-collapse: collapse;
        }

        .loss-table th {
            height: 30px;
            padding: 0 10px;
            color: #71717a;
            font-size: 11px;
            font-weight: 600;
            text-align: left;
            text-transform: uppercase;
            vertical-align: middle;
            white-space: nowrap;
        }

        .loss-table th:nth-child(2) {
            text-align: center;
        }

        .loss-table th:first-child,
        .loss-table td:first-child {
            padding-left: 0;
        }

        .dark .loss-table th {
            color: #f4f4f5;
        }

        .loss-table td {
            height: 71.17px;
            padding: 7px 10px;
            color: #18181b;
            font-size: 14px;
            font-weight: 500;
            text-align: left;
            vertical-align: middle;
        }

        .dark .loss-table td {
            color: #f4f4f5;
        }

        .loss-table__transition {
            margin-top: 3px;
            color: #71717a;
            font-size: 12px;
            font-weight: 400;
        }

        .dark .loss-table__transition {
            color: #a1a1aa;
        }

        .loss-table__metric {
            color: #dc2626;
            font-size: 14px;
            font-weight: 700;
            text-align: center;
            white-space: nowrap;
        }

        .dark .loss-table__metric {
            color: #f87171;
        }

        .loss-table__list-cell {
            text-align: center !important;
        }

        .loss-details-button {
            min-width: 76px;
            justify-content: center;
        }

        .loss-players-table {
            width: 100%;
            border-collapse: collapse;
        }

        .loss-players-table th,
        .loss-players-table td {
            padding: 9px 10px;
            border-bottom: 1px solid rgba(161, 161, 170, 0.2);
            text-align: left;
        }

        .loss-players-table th {
            color: #71717a;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .dark .loss-players-table th {
            color: #a1a1aa;
        }

        .loss-players-table a {
            color: inherit;
            font-weight: 600;
            text-decoration: none;
        }

        .loss-players-table a:hover {
            text-decoration: underline;
        }

        @media (max-width: 640px) {
            .funnel-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .funnel-period {
                width: 100%;
            }

            .funnel-period-range {
                grid-template-columns: 1fr;
            }

            .funnel-level {
                min-height: 54px;
                padding-inline: 20px;
            }

            .funnel-chart {
                width: 100%;
            }

            .funnel-widgets {
                grid-template-columns: minmax(0, 1fr);
            }

            .funnel-visual {
                grid-template-columns: minmax(185px, 1fr) 125px;
                gap: 6px;
            }

            .funnel-total__label,
            .funnel-table__header,
            .funnel-table__row {
                font-size: 9px;
            }

            .funnel-total__label {
                white-space: normal;
            }
        }
    </style>

    <div class="funnel-toolbar">
        <div class="funnel-period">
            <span class="funnel-label">Период</span>

            <div class="funnel-period-range">
                <div class="funnel-period-field">
                    <label for="funnel-period-from">С месяца</label>
                    <input
                        id="funnel-period-from"
                        type="month"
                        wire:model="pendingPeriodFrom"
                        x-on:click="$el.showPicker?.()"
                        class="funnel-period-input"
                    >
                </div>

                <div class="funnel-period-field">
                    <label for="funnel-period-until">По месяц</label>
                    <input
                        id="funnel-period-until"
                        type="month"
                        wire:model="pendingPeriodUntil"
                        x-on:click="$el.showPicker?.()"
                        class="funnel-period-input"
                    >
                </div>

                <button
                    type="button"
                    class="funnel-period-apply"
                    wire:click="applyPeriodRange"
                >
                    Применить
                </button>
            </div>

            <div class="funnel-period-help">
                Для одного месяца укажите одинаковый месяц в обоих полях. Максимальный период — 12 месяцев.
            </div>

            @error('periodRange')
                <div class="funnel-period-error">{{ $message }}</div>
            @enderror
        </div>

    </div>

    <div
        class="funnel-content"
        wire:loading.class="funnel-content--loading"
        wire:target="applyPeriodRange"
    >
        @php
            $stats = $this->getFunnelStats();
            $sourceStats = $this->getAttractionSourceStats();
            $transitionTimingStats = $this->getTransitionTimingStats();
        @endphp

        <div class="funnel-widgets">
        <div class="funnel-chart" role="img" aria-label="Воронка удержания новых игроков">
            <h2 class="funnel-widget-title">Воронка новых игроков</h2>

            <div class="funnel-visual">
                <div class="funnel-shape">
                    <div class="funnel-total">
                        <div class="funnel-total__label">
                            Новые игроки за выбранный период:
                            <span class="funnel-total__value">{{ $stats['total'] }}</span>
                        </div>
                    </div>

                    @foreach ($stats['stages'] as $stage)
                        @php
                            $level = $loop->index;
                            $topLeft = 0;
                            $topRight = 100 - ($level * 3.75);
                            $bottomLeft = 0;
                            $bottomRight = 100 - (($level + 1) * 3.75);
                            $iconLeft = -1.5;
                        @endphp

                        <div
                            class="funnel-level funnel-level--{{ $stage['key'] }}"
                            style="--top-left: {{ $topLeft }}%; --top-right: {{ $topRight }}%; --bottom-left: {{ $bottomLeft }}%; --bottom-right: {{ $bottomRight }}%; --icon-left: {{ $iconLeft }}%;"
                            aria-label="{{ $stage['label'] }}: {{ $stage['count'] }} игроков, {{ $stage['percentage'] }} процентов"
                        >
                            <div class="funnel-level__content">
                                <svg
                                    class="funnel-level__icon"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    @switch($stage['key'])
                                        @case('returned')
                                            <path d="M20 11a8 8 0 1 0-2.35 5.65" />
                                            <path d="M20 5v6h-6" />
                                            @break

                                        @case('interested')
                                            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                                            <circle cx="12" cy="12" r="2.75" />
                                            @break

                                        @case('engaged')
                                            <path d="M12 21V10" />
                                            <path d="M12 14c-4.5 0-7-2.3-7-6 4.5 0 7 2.3 7 6Z" />
                                            <path d="M12 10c0-4 2.3-6 6.5-6 0 4-2.3 6-6.5 6Z" />
                                            @break

                                        @case('contender')
                                            <circle cx="10.5" cy="13.5" r="7" />
                                            <circle cx="10.5" cy="13.5" r="3" />
                                            <path d="m12.5 11.5 7-7" />
                                            <path d="M16.5 4.5h3v3" />
                                            @break

                                        @case('active')
                                            <path d="m13.5 2-9 12h7l-1 8 9-12h-7l1-8Z" />
                                            @break

                                        @case('regular')
                                            <path d="m3.5 7 4.4 3.4L12 4l4.1 6.4L20.5 7l-1.8 10H5.3L3.5 7Z" />
                                            <path d="M5.3 17h13.4v2.5H5.3z" />
                                            @break

                                        @default
                                            <circle cx="12" cy="8" r="3.25" />
                                            <path d="M5.5 20c.45-4.1 2.6-6.15 6.5-6.15S18.05 15.9 18.5 20" />
                                    @endswitch
                                </svg>

                                <div>
                                    <div class="funnel-level__label">{{ $stage['label'] }}</div>
                                    <div class="funnel-level__metrics">{{ $stage['range'] }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="funnel-table" aria-label="Данные воронки">
                    <div class="funnel-table__header">
                        <div>Игроков</div>
                        <div>Доля от новых</div>
                    </div>

                    @foreach ($stats['stages'] as $stage)
                        <div class="funnel-table__row">
                            <div class="funnel-table__value">{{ $stage['count'] }}</div>
                            <div class="funnel-table__value">{{ number_format($stage['percentage'], 1, ',', ' ') }}%</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <section class="loss-widget" aria-labelledby="loss-widget-title">
            <h2 id="loss-widget-title" class="funnel-widget-title">Где теряем игроков</h2>

            <table class="loss-table">
                <thead>
                    <tr>
                        <th scope="col">Этап</th>
                        <th scope="col">Потеряли</th>
                        <th scope="col" aria-label="Детали"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stats['losses'] as $loss)
                        <tr>
                            <td>
                                <div>{{ $loss['label'] }}</div>
                                <div class="loss-table__transition">({{ $loss['transition'] }})</div>
                            </td>
                            <td>
                                <div class="loss-table__metric">
                                    {{ $loss['count'] }} / {{ number_format($loss['percentage'], 1, ',', ' ') }}%
                                </div>
                            </td>
                            <td class="loss-table__list-cell">
                                <x-filament::button
                                    type="button"
                                    size="xs"
                                    color="gray"
                                    class="loss-details-button"
                                    :disabled="$loss['count'] === 0"
                                    :loading-indicator="false"
                                    wire:click="openLossPlayers('{{ $loss['key'] }}')"
                                >
                                    Смотреть игроков
                                </x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="source-quality-widget" aria-labelledby="transition-timing-title">
            <h2 id="transition-timing-title" class="funnel-widget-title">
                Скорость перехода между этапами
            </h2>

            <div class="source-quality-table-wrap">
                <table class="source-quality-table">
                    <thead>
                        <tr>
                            <th scope="col" aria-label="Показатель"></th>
                            @foreach ($transitionTimingStats as $transition)
                                <th
                                    scope="col"
                                    @class([
                                        'transition-timing__heading',
                                        'transition-timing__heading--returned' => $transition['key'] === 'new_returned',
                                        'transition-timing__heading--interested' => $transition['key'] === 'returned_interested',
                                        'transition-timing__heading--engaged' => $transition['key'] === 'interested_engaged',
                                        'transition-timing__heading--contender' => $transition['key'] === 'engaged_contender',
                                        'transition-timing__heading--active' => $transition['key'] === 'contender_active',
                                        'transition-timing__heading--regular' => $transition['key'] === 'active_regular',
                                    ])
                                >
                                    @switch($transition['key'])
                                        @case('new_returned')
                                            <svg class="transition-timing__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M20 11a8 8 0 1 0-2.35 5.65" />
                                                <path d="M20 5v6h-6" />
                                            </svg>
                                            @break

                                        @case('returned_interested')
                                            <svg class="transition-timing__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                                                <circle cx="12" cy="12" r="2.75" />
                                            </svg>
                                            @break

                                        @case('interested_engaged')
                                            <svg class="transition-timing__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M12 21V10" />
                                                <path d="M12 14c-4.5 0-7-2.3-7-6 4.5 0 7 2.3 7 6Z" />
                                                <path d="M12 10c0-4 2.3-6 6.5-6 0 4-2.3 6-6.5 6Z" />
                                            </svg>
                                            @break

                                        @case('engaged_contender')
                                            <svg class="transition-timing__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <circle cx="10.5" cy="13.5" r="7" />
                                                <circle cx="10.5" cy="13.5" r="3" />
                                                <path d="m12.5 11.5 7-7" />
                                                <path d="M16.5 4.5h3v3" />
                                            </svg>
                                            @break

                                        @case('contender_active')
                                            <svg class="transition-timing__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="m13.5 2-9 12h7l-1 8 9-12h-7l1-8Z" />
                                            </svg>
                                            @break

                                        @case('active_regular')
                                            <svg class="transition-timing__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="m3.5 7 4.4 3.4L12 4l4.1 6.4L20.5 7l-1.8 10H5.3L3.5 7Z" />
                                                <path d="M5.3 17h13.4v2.5H5.3z" />
                                            </svg>
                                            @break
                                    @endswitch

                                    <div>{{ $transition['label'] }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>В среднем</td>
                            @foreach ($transitionTimingStats as $transition)
                                <td>
                                    {{ $transition['average_days'] === null
                                        ? '—'
                                        : number_format($transition['average_days'], 1, ',', ' ') . ' дн.' }}
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td>Медиана</td>
                            @foreach ($transitionTimingStats as $transition)
                                <td>
                                    {{ $transition['median_days'] === null
                                        ? '—'
                                        : number_format($transition['median_days'], 1, ',', ' ') . ' дн.' }}
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="source-quality-widget" aria-labelledby="source-quality-title">
            <h2 id="source-quality-title" class="funnel-widget-title">
                Качество привлечения по источникам
            </h2>

            <div class="source-quality-table-wrap">
                <table class="source-quality-table funnel-stage-table">
                    <thead>
                        <tr>
                            <th scope="col" aria-label="Источник"></th>
                            @foreach ($sourceStats['stages'] as $stage)
                                <th
                                    scope="col"
                                    @class([
                                        'transition-timing__heading',
                                        'transition-timing__heading--new' => $stage['key'] === 'new',
                                        'transition-timing__heading--returned' => $stage['key'] === 'returned',
                                        'transition-timing__heading--interested' => $stage['key'] === 'interested',
                                        'transition-timing__heading--engaged' => $stage['key'] === 'engaged',
                                        'transition-timing__heading--contender' => $stage['key'] === 'contender',
                                        'transition-timing__heading--active' => $stage['key'] === 'active',
                                        'transition-timing__heading--regular' => $stage['key'] === 'regular',
                                    ])
                                >
                                    <div class="stage-heading__content">
                                        @include('filament.pages.partials.funnel-stage-icon', [
                                            'stageKey' => $stage['key'],
                                            'class' => 'stage-heading__icon',
                                        ])
                                        <span>{{ $stage['label'] }}</span>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sourceStats['rows'] as $source)
                            @php
                                $sourceNewCount = $source['counts']['new'];
                            @endphp
                            <tr>
                                <td>
                                    <span class="source-quality-table__source">
                                        @switch($source['name'])
                                            @case('Instagram')
                                                <svg class="source-quality-table__source-icon source-quality-table__source-icon--instagram" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <rect x="3" y="3" width="18" height="18" rx="5" />
                                                    <circle cx="12" cy="12" r="4" />
                                                    <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none" />
                                                </svg>
                                                @break

                                            @case('Telegram')
                                                <svg class="source-quality-table__source-icon source-quality-table__source-icon--telegram" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="m3 11 18-8-4.5 18-5.2-6.1L8 18v-5l9-6-11 5-3-1Z" />
                                                </svg>
                                                @break

                                            @case('YouTube')
                                                <svg class="source-quality-table__source-icon source-quality-table__source-icon--youtube" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true">
                                                    <rect x="2.5" y="5.5" width="19" height="13" rx="4" />
                                                    <path d="m10 9 5 3-5 3V9Z" />
                                                </svg>
                                                @break

                                            @case('Поиск в Google/Яндекс')
                                                <svg class="source-quality-table__source-icon source-quality-table__source-icon--search" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                                                    <circle cx="10.5" cy="10.5" r="6.5" />
                                                    <path d="m15.5 15.5 5 5" />
                                                </svg>
                                                @break

                                            @case('Рекомендации знакомых')
                                                <svg class="source-quality-table__source-icon source-quality-table__source-icon--referral" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                                                    <circle cx="9" cy="8" r="3" />
                                                    <circle cx="17" cy="9" r="2.5" />
                                                    <path d="M3.5 19c.4-4 2.2-6 5.5-6s5.1 2 5.5 6" />
                                                    <path d="M14 14c3.8-.6 5.8 1 6.5 4" />
                                                </svg>
                                                @break

                                            @default
                                                <svg class="source-quality-table__source-icon source-quality-table__source-icon--other" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                    <circle cx="5" cy="12" r="1.7" />
                                                    <circle cx="12" cy="12" r="1.7" />
                                                    <circle cx="19" cy="12" r="1.7" />
                                                </svg>
                                        @endswitch

                                        <span>{{ $source['name'] }}</span>
                                    </span>
                                </td>
                                @foreach ($sourceStats['stages'] as $stage)
                                    @php
                                        $stageCount = $source['counts'][$stage['key']];
                                        $stagePercentage = $stage['key'] === 'new'
                                            ? 100
                                            : ($sourceNewCount === 0
                                                ? 0
                                                : round(($stageCount / $sourceNewCount) * 100, 1));
                                    @endphp
                                    <td>
                                        <span class="source-quality-table__metric">
                                            {{ $stageCount }} / {{ number_format($stagePercentage, 1, ',', ' ') }}%
                                        </span>
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">Источники пока не добавлены.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section
            class="source-quality-widget"
            aria-labelledby="player-dynamics-title"
            x-data="{
                page: 1,
                perPage: '12',
                total: {{ count($playerDynamics['rows']) }},
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
            <div class="dynamics-toolbar">
                <h2 id="player-dynamics-title" class="funnel-widget-title">
                    Динамика игроков по месяцам
                </h2>

                <label class="dynamics-page-size">
                    <select
                        x-model="perPage"
                        x-on:change="page = 1"
                        aria-label="Количество отображаемых месяцев"
                    >
                        <option value="3">3 месяца</option>
                        <option value="6">6 месяцев</option>
                        <option value="12">12 месяцев</option>
                        <option value="all">Весь период</option>
                    </select>
                </label>
            </div>

            <div class="source-quality-table-wrap">
                <table class="source-quality-table funnel-stage-table">
                    <thead>
                        <tr>
                            <th scope="col" aria-label="Месяц"></th>
                            @foreach ($playerDynamics['stages'] as $stage)
                                <th
                                    scope="col"
                                    @class([
                                        'transition-timing__heading',
                                        'transition-timing__heading--new' => $stage['key'] === 'new',
                                        'transition-timing__heading--returned' => $stage['key'] === 'returned',
                                        'transition-timing__heading--interested' => $stage['key'] === 'interested',
                                        'transition-timing__heading--engaged' => $stage['key'] === 'engaged',
                                        'transition-timing__heading--contender' => $stage['key'] === 'contender',
                                        'transition-timing__heading--active' => $stage['key'] === 'active',
                                        'transition-timing__heading--regular' => $stage['key'] === 'regular',
                                    ])
                                >
                                    <div class="stage-heading__content">
                                    @switch($stage['key'])
                                        @case('returned')
                                            <svg class="transition-timing__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M20 11a8 8 0 1 0-2.35 5.65" />
                                                <path d="M20 5v6h-6" />
                                            </svg>
                                            @break

                                        @case('interested')
                                            <svg class="transition-timing__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                                                <circle cx="12" cy="12" r="2.75" />
                                            </svg>
                                            @break

                                        @case('engaged')
                                            <svg class="transition-timing__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M12 21V10" />
                                                <path d="M12 14c-4.5 0-7-2.3-7-6 4.5 0 7 2.3 7 6Z" />
                                                <path d="M12 10c0-4 2.3-6 6.5-6 0 4-2.3 6-6.5 6Z" />
                                            </svg>
                                            @break

                                        @case('contender')
                                            <svg class="transition-timing__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <circle cx="10.5" cy="13.5" r="7" />
                                                <circle cx="10.5" cy="13.5" r="3" />
                                                <path d="m12.5 11.5 7-7" />
                                                <path d="M16.5 4.5h3v3" />
                                            </svg>
                                            @break

                                        @case('active')
                                            <svg class="transition-timing__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="m13.5 2-9 12h7l-1 8 9-12h-7l1-8Z" />
                                            </svg>
                                            @break

                                        @case('regular')
                                            <svg class="transition-timing__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="m3.5 7 4.4 3.4L12 4l4.1 6.4L20.5 7l-1.8 10H5.3L3.5 7Z" />
                                                <path d="M5.3 17h13.4v2.5H5.3z" />
                                            </svg>
                                            @break

                                        @default
                                            <svg class="transition-timing__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                                                <circle cx="12" cy="8" r="3.25" />
                                                <path d="M5.5 20c.45-4.1 2.6-6.15 6.5-6.15S18.05 15.9 18.5 20" />
                                            </svg>
                                    @endswitch

                                        <span>{{ $stage['label'] }}</span>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($playerDynamics['rows'] as $monthIndex => $month)
                            @php
                                $monthNewCount = $month['counts']['new'];
                            @endphp
                            <tr x-show="visible({{ $monthIndex }})">
                                <td>
                                    <div>{{ $month['label'] }}</div>
                                    @if ($month['month'] === now()->format('Y-m'))
                                        <div class="loss-table__transition">(неполный месяц)</div>
                                    @endif
                                </td>
                                @foreach ($playerDynamics['stages'] as $stage)
                                    @php
                                        $stageCount = $month['counts'][$stage['key']];
                                        $stagePercentage = $stage['key'] === 'new'
                                            ? 100
                                            : ($monthNewCount === 0
                                                ? 0
                                                : round(($stageCount / $monthNewCount) * 100, 1));
                                    @endphp
                                    <td>
                                        <span class="source-quality-table__metric">
                                            {{ $stageCount }} / {{ number_format($stagePercentage, 1, ',', ' ') }}%
                                        </span>
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">Пока нет игроков с датой первого визита.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="dynamics-pagination" x-show="pages > 1" x-cloak>
                <button type="button" x-on:click="page--" x-bind:disabled="page === 1">
                    Назад
                </button>
                <span class="dynamics-pagination__status">
                    Страница <span x-text="page"></span> из <span x-text="pages"></span>
                </span>
                <button type="button" x-on:click="page++" x-bind:disabled="page === pages">
                    Далее
                </button>
            </div>
        </section>
        </div>

        <x-filament::modal
            id="loss-players-modal"
            width="2xl"
            :heading="$lossPlayersHeading"
        >
            @if (count($lossPlayers))
                <table class="loss-players-table">
                    <thead>
                        <tr>
                            <th scope="col">Игрок</th>
                            <th scope="col">Первое посещение</th>
                            <th scope="col">Визитов</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lossPlayers as $player)
                            <tr>
                                <td>
                                    <a href="{{ $player['url'] }}">
                                        {{ $player['nickname'] }}
                                    </a>
                                </td>
                                <td>{{ $player['first_visit_at'] }}</td>
                                <td>{{ $player['evenings_count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div>Игроки не найдены.</div>
            @endif
        </x-filament::modal>
    </div>
</x-filament-panels::page>
