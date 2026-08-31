@php
    $statusKey = match ($label) {
        'Игрок сезона' => 'season-player',
        'Клубный игрок' => 'club-player',
        default => 'guest',
    };
@endphp

<span class="player-status player-activity-status player-activity-status--{{ $statusKey }}">
    @switch($statusKey)
        @case('season-player')
            <svg class="player-status__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M8 21h8M12 17v4M7 4h10v4a5 5 0 0 1-10 0V4Z" />
                <path d="M7 6H4v2a4 4 0 0 0 4 4M17 6h3v2a4 4 0 0 1-4 4" />
            </svg>
            @break

        @case('club-player')
            <svg class="player-status__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m3.5 7 4.4 3.4L12 4l4.1 6.4L20.5 7l-1.8 10H5.3L3.5 7Z" />
                <path d="M5.3 17h13.4v2.5H5.3z" />
            </svg>
            @break

        @default
            <svg class="player-status__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="8" r="3.25" />
                <path d="M5.5 20c.45-4.1 2.6-6.15 6.5-6.15S18.05 15.9 18.5 20" />
            </svg>
    @endswitch

    <span>{{ $label }}</span>
</span>
