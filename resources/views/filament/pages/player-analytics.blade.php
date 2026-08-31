<x-filament-panels::page>
    <style>
        .player-analytics-centered-cell .fi-ta-col,
        .player-analytics-centered-cell .fi-ta-text {
            justify-content: center;
            text-align: center;
        }

        .player-status {
            box-sizing: border-box;
            display: inline-flex;
            width: 140px;
            align-items: center;
            justify-content: center;
            gap: 4px;
            border-radius: 6px;
            padding: 4px 7px;
            color: #18181b;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.2;
            white-space: nowrap;
        }

        .player-status__icon {
            width: 15px;
            height: 15px;
            flex: 0 0 auto;
        }

        .player-status--new { background: rgb(229 231 235 / 60%); }
        .player-status--returned { background: rgb(186 230 253 / 60%); }
        .player-status--interested { background: rgb(147 197 253 / 60%); }
        .player-status--engaged { background: rgb(216 180 254 / 60%); }
        .player-status--contender { background: rgb(254 215 170 / 60%); }
        .player-status--active { background: rgb(187 247 208 / 60%); }
        .player-status--regular { background: rgb(254 240 138 / 60%); }

        .player-activity-status {
            /* Размер наследуется от общего бейджа статуса. */
        }

        .player-activity-status--season-player { background: rgb(254 240 138 / 70%); }
        .player-activity-status--club-player { background: rgb(216 180 254 / 65%); }
        .player-activity-status--guest { background: rgb(229 231 235 / 60%); }

        .dark .player-status {
            color: #fff;
        }

        .dark .player-status--new { background: rgb(75 85 99 / 68%); }
        .dark .player-status--returned { background: rgb(3 105 161 / 68%); }
        .dark .player-status--interested { background: rgb(29 78 216 / 68%); }
        .dark .player-status--engaged { background: rgb(126 34 206 / 68%); }
        .dark .player-status--contender { background: rgb(194 65 12 / 68%); }
        .dark .player-status--active { background: rgb(21 128 61 / 68%); }
        .dark .player-status--regular { background: rgb(161 98 7 / 68%); }
        .dark .player-activity-status--season-player { background: rgb(161 98 7 / 68%); }
        .dark .player-activity-status--club-player { background: rgb(126 34 206 / 68%); }
        .dark .player-activity-status--guest { background: rgb(75 85 99 / 68%); }
    </style>

    {{ $this->table }}
</x-filament-panels::page>
