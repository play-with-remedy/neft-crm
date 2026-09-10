<div class="salary-details">
    <style>
        .salary-details { color: #18181b; }
        .dark .salary-details { color: #f4f4f5; }
        .salary-details__summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 20px; }
        .salary-details__stat { min-width: 0; border: 1px solid #e4e4e7; border-radius: 14px; background: #fafafa; padding: 14px 16px; }
        .dark .salary-details__stat { border-color: rgba(255,255,255,.1); background: rgba(255,255,255,.04); }
        .salary-details__stat-label { margin-bottom: 5px; color: #71717a; font-size: 12px; font-weight: 600; }
        .dark .salary-details__stat-label { color: #a1a1aa; }
        .salary-details__stat-value { overflow: hidden; font-size: 17px; font-weight: 700; line-height: 1.25; text-overflow: ellipsis; white-space: nowrap; }
        .salary-details__stat--accent { border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.08); }
        .salary-details__stat--accent .salary-details__stat-value { color: #d97706; }
        .dark .salary-details__stat--accent .salary-details__stat-value { color: #fbbf24; }
        .salary-details__list { display: grid; gap: 10px; }
        .salary-details__item { display: grid; grid-template-columns: 112px minmax(130px, 1.4fr) minmax(120px, 1fr) minmax(100px, .8fr) auto; align-items: center; gap: 14px; border: 1px solid #e4e4e7; border-radius: 14px; padding: 14px 16px; }
        .dark .salary-details__item { border-color: rgba(255,255,255,.1); }
        .salary-details__date { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; white-space: nowrap; }
        .salary-details__date-icon { display: grid; width: 30px; height: 30px; flex: none; place-items: center; border-radius: 9px; background: rgba(245,158,11,.12); color: #d97706; }
        .dark .salary-details__date-icon { color: #fbbf24; }
        .salary-details__date-icon svg { width: 16px; height: 16px; }
        .salary-details__cell { min-width: 0; overflow: hidden; color: #52525b; font-size: 13px; text-overflow: ellipsis; white-space: nowrap; }
        .dark .salary-details__cell { color: #d4d4d8; }
        .salary-details__cell--type { color: inherit; font-size: 14px; font-weight: 700; }
        .salary-details__cell--role { color: #2563eb; font-weight: 650; }
        .dark .salary-details__cell--role { color: #93c5fd; }
        .salary-details__amount { padding-left: 12px; font-size: 15px; font-weight: 750; white-space: nowrap; }
        .salary-details__pagination { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 18px; padding-top: 16px; border-top: 1px solid #e4e4e7; }
        .dark .salary-details__pagination { border-color: rgba(255,255,255,.1); }
        .salary-details__pagination-status { color: #71717a; font-size: 12px; }
        .salary-details__pagination-actions { display: flex; gap: 8px; }
        .salary-details__pagination-button { border: 1px solid #d4d4d8; border-radius: 9px; padding: 7px 12px; font-size: 12px; font-weight: 650; }
        .salary-details__pagination-button:hover:not(:disabled) { border-color: #f59e0b; color: #d97706; }
        .salary-details__pagination-button:disabled { cursor: not-allowed; opacity: .4; }
        .dark .salary-details__pagination-button { border-color: rgba(255,255,255,.14); }
        .salary-details__empty { display: grid; min-height: 180px; place-items: center; border: 1px dashed #d4d4d8; border-radius: 14px; color: #71717a; text-align: center; }
        .dark .salary-details__empty { border-color: rgba(255,255,255,.15); color: #a1a1aa; }
        .salary-details__empty svg { width: 34px; height: 34px; margin: 0 auto 10px; opacity: .65; }
        @media (max-width: 640px) {
            .salary-details__summary { grid-template-columns: 1fr 1fr; }
            .salary-details__stat:first-child { grid-column: 1 / -1; }
            .salary-details__item { grid-template-columns: 1fr auto; gap: 8px 12px; padding: 13px; }
            .salary-details__date { grid-column: 1 / -1; }
            .salary-details__cell { grid-column: 1; white-space: normal; }
            .salary-details__cell--project { color: #71717a; font-size: 12px; }
            .salary-details__cell--role { grid-column: 2; grid-row: 2; align-self: start; text-align: right; }
            .salary-details__amount { align-self: end; }
        }
    </style>

    <div class="salary-details__summary">
        <div class="salary-details__stat">
            <div class="salary-details__stat-label">Период</div>
            <div class="salary-details__stat-value">{{ $periodLabel }}</div>
        </div>
        <div class="salary-details__stat">
            <div class="salary-details__stat-label">Вечеров</div>
            <div class="salary-details__stat-value">{{ number_format($eveningsCount, 0, ',', ' ') }}</div>
        </div>
        <div class="salary-details__stat salary-details__stat--accent">
            <div class="salary-details__stat-label">Начислено</div>
            <div class="salary-details__stat-value">{{ number_format($totalSalary, 0, ',', ' ') }} BYN</div>
        </div>
    </div>

    @if ($evenings->isNotEmpty())
        <div class="salary-details__list">
            @foreach ($evenings as $evening)
                <article class="salary-details__item" wire:key="staff-evening-{{ $evening->id }}">
                    <div class="salary-details__date">
                        <span class="salary-details__date-icon"><x-filament::icon icon="heroicon-m-calendar-days" /></span>
                        {{ $evening->played_at->format('d.m.Y') }}
                    </div>
                    <div class="salary-details__cell salary-details__cell--type">{{ $evening->eveningType?->name ?? 'Вечер без типа' }}</div>
                    <div class="salary-details__cell salary-details__cell--project">{{ $evening->project?->name ?? 'Без проекта' }}</div>
                    <div class="salary-details__cell salary-details__cell--role">{{ $evening->staff->pluck('role')->unique()->map(fn ($role) => $roleLabels[$role] ?? $role)->join(', ') }}</div>
                    <div class="salary-details__amount">{{ number_format((int) $evening->staff->sum('salary'), 0, ',', ' ') }} BYN</div>
                </article>
            @endforeach
        </div>

        @if ($evenings->hasPages())
            <div class="salary-details__pagination">
                <div class="salary-details__pagination-status">
                    {{ $evenings->firstItem() }}–{{ $evenings->lastItem() }} из {{ $evenings->total() }}
                </div>
                <div class="salary-details__pagination-actions">
                    <button type="button" class="salary-details__pagination-button" wire:click="previousPage('staffEveningsPage')" @disabled($evenings->onFirstPage())>Назад</button>
                    <button type="button" class="salary-details__pagination-button" wire:click="nextPage('staffEveningsPage')" @disabled(! $evenings->hasMorePages())>Далее</button>
                </div>
            </div>
        @endif
    @else
        <div class="salary-details__empty">
            <div><x-filament::icon icon="heroicon-o-calendar-days" />За выбранный период вечеров нет</div>
        </div>
    @endif
</div>
