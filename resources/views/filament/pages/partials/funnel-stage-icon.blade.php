@switch($stageKey)
    @case('returned')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M20 11a8 8 0 1 0-2.35 5.65" />
            <path d="M20 5v6h-6" />
        </svg>
        @break

    @case('interested')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
            <circle cx="12" cy="12" r="2.75" />
        </svg>
        @break

    @case('engaged')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 21V10" />
            <path d="M12 14c-4.5 0-7-2.3-7-6 4.5 0 7 2.3 7 6Z" />
            <path d="M12 10c0-4 2.3-6 6.5-6 0 4-2.3 6-6.5 6Z" />
        </svg>
        @break

    @case('contender')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="10.5" cy="13.5" r="7" />
            <circle cx="10.5" cy="13.5" r="3" />
            <path d="m12.5 11.5 7-7" />
            <path d="M16.5 4.5h3v3" />
        </svg>
        @break

    @case('active')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m13.5 2-9 12h7l-1 8 9-12h-7l1-8Z" />
        </svg>
        @break

    @case('regular')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m3.5 7 4.4 3.4L12 4l4.1 6.4L20.5 7l-1.8 10H5.3L3.5 7Z" />
            <path d="M5.3 17h13.4v2.5H5.3z" />
        </svg>
        @break

    @default
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
            <circle cx="12" cy="8" r="3.25" />
            <path d="M5.5 20c.45-4.1 2.6-6.15 6.5-6.15S18.05 15.9 18.5 20" />
        </svg>
@endswitch
