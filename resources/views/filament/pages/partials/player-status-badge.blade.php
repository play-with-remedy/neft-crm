@if ($stageKey === 'none')
    <span>—</span>
@else
    <span class="player-status player-status--{{ $stageKey }}">
        @include('filament.pages.partials.funnel-stage-icon', [
            'stageKey' => $stageKey,
            'class' => 'player-status__icon',
        ])
        <span>{{ $label }}</span>
    </span>
@endif
