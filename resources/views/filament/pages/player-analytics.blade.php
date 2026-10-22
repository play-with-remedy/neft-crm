<x-filament-panels::page>
    <style>
        .player-analytics-centered-cell .fi-ta-col,
        .player-analytics-centered-cell .fi-ta-text {
            justify-content: center;
            text-align: center;
        }
    </style>

    @include('filament.pages.partials.player-status-styles')

    {{ $this->table }}
</x-filament-panels::page>
