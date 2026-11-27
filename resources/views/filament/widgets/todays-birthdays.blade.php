<x-filament-widgets::widget class="fi-wi-table todays-birthdays-widget">
    <style>
        .club-dashboard .fi-wi-chart > .fi-section {
            height: 100%;
        }

        .todays-birthdays-widget {
            display: flex;
            min-height: 440px;
        }

        .todays-birthdays-widget > .fi-ta {
            display: flex;
            flex: 1;
            min-height: 0;
        }

        .todays-birthdays-widget .fi-ta-ctn {
            flex: 1;
            height: 100%;
        }

        .todays-birthdays-widget .fi-ta-main {
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .todays-birthdays-widget .fi-ta-header-ctn {
            flex: 0 0 auto;
        }

        .todays-birthdays-widget .fi-ta-main:has(> .fi-ta-empty-state) > .fi-ta-content-ctn {
            display: none;
        }

        .todays-birthdays-widget .fi-ta-empty-state {
            display: grid;
            flex: 1;
            place-content: center;
        }

        .todays-birthdays-widget .birthdays-heading {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .todays-birthdays-widget .birthdays-heading__icon {
            width: 20px;
            height: 20px;
            flex: 0 0 auto;
            color: #f59e0b;
        }

        .todays-birthdays-widget .birthdays-heading__count {
            flex: 0 0 auto;
            color: #b45309;
            font-variant-numeric: tabular-nums;
        }

        .dark .todays-birthdays-widget .birthdays-heading__count {
            color: #fbbf24;
        }

        .todays-birthdays-widget .fi-ta-content-ctn {
            flex: 1 1 0;
            height: 0;
            min-height: 0;
            overflow: auto;
            overscroll-behavior: contain;
        }

        .todays-birthdays-widget .fi-ta-table {
            table-layout: fixed;
        }

        .todays-birthdays-widget .fi-ta-table > thead {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #f9fafb;
        }

        .todays-birthdays-widget .fi-ta-header-cell {
            height: 40px;
            padding-top: 8px;
            padding-bottom: 8px;
        }

        .todays-birthdays-widget .fi-ta-table > tbody > tr {
            height: 48px;
        }

        .todays-birthdays-widget .fi-ta-text {
            overflow: hidden;
            padding-top: 12px;
            padding-bottom: 12px;
        }

        .todays-birthdays-widget .fi-ta-text-item {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            line-height: 20px;
        }

        .todays-birthdays-widget .fi-ta-table > thead > tr > th:first-child,
        .todays-birthdays-widget .fi-ta-table > tbody > tr > td:first-child {
            width: 56px;
            min-width: 56px;
            padding-inline: 0;
        }

        .todays-birthdays-widget .fi-ta-table > tbody > tr > td:first-child .fi-ta-text {
            padding-inline: 8px;
            overflow: visible;
        }

        .todays-birthdays-widget .fi-ta-table > tbody > tr > td:first-child .fi-ta-text-item {
            overflow: visible;
            text-overflow: clip;
            font-variant-numeric: tabular-nums;
        }

        .dark .todays-birthdays-widget .fi-ta-table > thead {
            background: #18181b;
        }
    </style>

    {{ $this->table }}
</x-filament-widgets::widget>
