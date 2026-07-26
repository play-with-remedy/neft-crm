<x-filament-widgets::widget
    class="club-welcome-widget"
    style="grid-column: 1 / -1;"
>
<div class="club-welcome">
    <div class="club-welcome__glow club-welcome__glow--one"></div>
    <div class="club-welcome__glow club-welcome__glow--two"></div>

    <div class="club-welcome__content">
        <div>
            <p class="club-welcome__eyebrow">{{ $month }}</p>
            <h2>Добрый день{{ $userName ? ', ' . $userName : '' }}!</h2>
            <p class="club-welcome__text">
                Здесь собраны главные показатели клуба, последние вечера и быстрые действия.
            </p>
        </div>

        <div class="club-welcome__actions">
            <a class="club-welcome__button club-welcome__button--secondary" href="{{ $cashBookUrl }}">
                <x-filament::icon icon="heroicon-o-chart-bar" />
                Кассовая книга
            </a>
            <a class="club-welcome__button club-welcome__button--secondary" href="{{ $createPlayerUrl }}">
                <x-filament::icon icon="heroicon-o-user-plus" />
                Новый игрок
            </a>
            <a class="club-welcome__button club-welcome__button--primary" href="{{ $createEveningUrl }}">
                <x-filament::icon icon="heroicon-o-plus" />
                Добавить вечер
            </a>
        </div>
    </div>
</div>

<style>
    .club-welcome {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        border-radius: 1.25rem;
        padding: clamp(1.5rem, 3vw, 2.25rem);
        color: #fff;
        background:
            linear-gradient(120deg, rgba(24, 24, 27, .98), rgba(69, 26, 3, .94)),
            #18181b;
        box-shadow: 0 18px 45px -26px rgba(120, 53, 15, .7);
    }

    .club-welcome::after {
        content: "";
        position: absolute;
        inset: 0;
        z-index: -1;
        opacity: .12;
        background-image:
            linear-gradient(rgba(255, 255, 255, .16) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, .16) 1px, transparent 1px);
        background-size: 42px 42px;
        mask-image: linear-gradient(to left, #000, transparent 72%);
    }

    .club-welcome__content {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 2rem;
    }

    .club-welcome__eyebrow {
        margin-bottom: .55rem;
        color: #fbbf24;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .club-welcome h2 {
        font-size: clamp(1.45rem, 2.5vw, 2rem);
        font-weight: 750;
        line-height: 1.15;
        letter-spacing: -.025em;
    }

    .club-welcome__text {
        max-width: 42rem;
        margin-top: .65rem;
        color: rgba(255, 255, 255, .7);
        font-size: .9rem;
        line-height: 1.55;
    }

    .club-welcome__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: .65rem;
        flex-shrink: 0;
    }

    .club-welcome__button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        min-height: 2.55rem;
        border-radius: .75rem;
        padding: .6rem .9rem;
        font-size: .8rem;
        font-weight: 650;
        transition: transform .15s ease, background-color .15s ease, border-color .15s ease;
    }

    .club-welcome__button:hover {
        transform: translateY(-1px);
    }

    .club-welcome__button svg {
        width: 1rem;
        height: 1rem;
    }

    .club-welcome__button--secondary {
        border: 1px solid rgba(255, 255, 255, .15);
        color: rgba(255, 255, 255, .9);
        background: rgba(255, 255, 255, .07);
        backdrop-filter: blur(8px);
    }

    .club-welcome__button--secondary:hover {
        border-color: rgba(255, 255, 255, .25);
        background: rgba(255, 255, 255, .12);
    }

    .club-welcome__button--primary {
        color: #422006;
        background: #fbbf24;
        box-shadow: 0 8px 24px -12px rgba(251, 191, 36, .8);
    }

    .club-welcome__button--primary:hover {
        background: #fcd34d;
    }

    .club-welcome__glow {
        position: absolute;
        z-index: -1;
        border-radius: 9999px;
        filter: blur(4px);
        pointer-events: none;
    }

    .club-welcome__glow--one {
        width: 14rem;
        height: 14rem;
        top: -9rem;
        right: 12%;
        background: rgba(245, 158, 11, .2);
    }

    .club-welcome__glow--two {
        width: 9rem;
        height: 9rem;
        right: -3rem;
        bottom: -5rem;
        background: rgba(220, 38, 38, .15);
    }

    @media (max-width: 900px) {
        .club-welcome__content {
            align-items: flex-start;
            flex-direction: column;
            gap: 1.25rem;
        }

        .club-welcome__actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 520px) {
        .club-welcome__actions,
        .club-welcome__button {
            width: 100%;
        }
    }
</style>
</x-filament-widgets::widget>
