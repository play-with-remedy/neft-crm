<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Доступ ограничен</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}">

    <style>
        :root {
            color-scheme: dark;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: #fafafa;
            background:
                radial-gradient(circle at 78% 18%, rgba(245, 158, 11, .16), transparent 28rem),
                radial-gradient(circle at 14% 82%, rgba(120, 53, 15, .2), transparent 24rem),
                #09090b;
        }

        .page {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1.5rem;
        }

        .card {
            position: relative;
            width: min(100%, 38rem);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 1.5rem;
            padding: clamp(1.75rem, 6vw, 3rem);
            text-align: center;
            background: linear-gradient(145deg, rgba(39, 39, 42, .96), rgba(24, 24, 27, .94));
            box-shadow: 0 2rem 5rem -2rem rgba(0, 0, 0, .75);
        }

        .card::before {
            content: "";
            position: absolute;
            width: 12rem;
            height: 12rem;
            top: -7rem;
            right: -4rem;
            border-radius: 9999px;
            background: rgba(245, 158, 11, .13);
            filter: blur(4px);
        }

        .logo {
            width: 3.75rem;
            height: 3.75rem;
            margin-bottom: 1.4rem;
            border-radius: 1rem;
            box-shadow: 0 .75rem 2rem -.75rem rgba(245, 158, 11, .45);
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            border: 1px solid rgba(251, 191, 36, .22);
            border-radius: 9999px;
            padding: .4rem .7rem;
            color: #fcd34d;
            background: rgba(245, 158, 11, .08);
            font-size: .75rem;
            font-weight: 750;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .status svg {
            width: 1rem;
            height: 1rem;
        }

        h1 {
            margin: 1.2rem 0 .75rem;
            font-size: clamp(1.8rem, 6vw, 2.6rem);
            line-height: 1.1;
            letter-spacing: -.035em;
        }

        p {
            max-width: 29rem;
            margin: 0 auto;
            color: #a1a1aa;
            font-size: .98rem;
            line-height: 1.65;
        }

        .actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: .75rem;
            margin-top: 1.75rem;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            min-height: 2.75rem;
            border: 1px solid transparent;
            border-radius: .8rem;
            padding: .65rem 1rem;
            color: inherit;
            font: inherit;
            font-size: .875rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: transform .15s ease, background-color .15s ease, border-color .15s ease;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button--primary {
            color: #422006;
            background: #fbbf24;
        }

        .button--primary:hover {
            background: #fcd34d;
        }

        .button svg {
            width: 1.05rem;
            height: 1.05rem;
        }

        @media (max-width: 480px) {
            .actions,
            .button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="card" aria-labelledby="error-title">
            <img class="logo" src="{{ asset('favicon.svg') }}" alt="">

            <div>
                <span class="status">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 0 0-9 0v3.75m-.75 0h10.5A2.25 2.25 0 0 1 19.5 12.75v6A2.25 2.25 0 0 1 17.25 21H6.75a2.25 2.25 0 0 1-2.25-2.25v-6a2.25 2.25 0 0 1 2.25-2.25Z" />
                    </svg>
                    Ошибка 403
                </span>
            </div>

            <h1 id="error-title">Доступ ограничен</h1>
            <p>
                У вашей учётной записи нет прав для просмотра этого раздела.
                Если доступ необходим, обратитесь к суперадмину.
            </p>

            <div class="actions">
                <a class="button button--primary" href="{{ url('/admin') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.125 1.125 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />
                    </svg>
                    На главную
                </a>
            </div>
        </section>
    </main>
</body>
</html>
