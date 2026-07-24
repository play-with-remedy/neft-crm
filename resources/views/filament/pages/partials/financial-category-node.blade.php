@php
    $isLeaf = $category->childrenRecursive->isEmpty();
    $isCalculated = in_array($category->code, [
        'team_event_salaries',
        'team_event_other_expenses',
    ], true);

    $amount = $isLeaf && ! $isCalculated
        ? ($values[$category->id]['amount'] ?? null)
        : $this->categoryTotal($category);

    $details = $isLeaf && ! $isCalculated
        ? ($values[$category->id]['details'] ?? null)
        : null;
@endphp

<div
    @if (! $isLeaf)
        x-data="{ open: false }"
    @endif
>
<div
    @class([
        'financial-row',
        'financial-row--root' => $level === 1,
        'financial-row--parent' => ! $isLeaf && $level !== 1,
    ])
>
    <div
        class="
            financial-name
            financial-name--level-{{ $level }}
        "
    >
        @if (! $isLeaf)
            <button
                type="button"
                class="financial-toggle"
                x-on:click="open = ! open"
                x-bind:aria-expanded="open"
                aria-label="Свернуть или раскрыть ветку"
            >
                <span
                    class="financial-toggle-icon"
                    x-bind:class="{ 'financial-toggle-icon--open': open }"
                >
                    ›
                </span>
            </button>
        @endif

        {{ $category->name }}
    </div>

    <div>
        @if ($isEditing && $isLeaf && ! $isCalculated)
            <input
                type="number"
                min="0"
                step="0.01"
                placeholder="0.00"
                wire:model.defer="values.{{ $category->id }}.amount"
                class="financial-amount-input"
            >

            @error(
                'values.'
                . $category->id
                . '.amount'
            )
                <div class="financial-error">
                    {{ $message }}
                </div>
            @enderror
        @else
            <div class="financial-amount">
                {{ $this->formatAmount($amount) }}
            </div>
        @endif
    </div>

    <div class="financial-percentage">
        {{
            $this->formatPercentage(
                $amount,
                $totalRevenue
            )
        }}
    </div>

    <div>
        @if ($isCalculated)
            <div class="financial-details">
                {{
                    $category->code === 'team_event_salaries'
                        ? 'Рассчитано по зарплатам в кассовой книге'
                        : 'Рассчитано по расходам в кассовой книге'
                }}
            </div>
        @elseif ($isEditing && $isLeaf)
            <input
                type="text"
                placeholder="Комментарий или расшифровка"
                wire:model.defer="values.{{ $category->id }}.details"
                class="financial-details-input"
            >

            @error(
                'values.'
                . $category->id
                . '.details'
            )
                <div class="financial-error">
                    {{ $message }}
                </div>
            @enderror
        @elseif ($isLeaf)
            <div class="financial-details">
                {{ filled($details) ? $details : '—' }}
            </div>
        @else
            <div class="financial-details">
                —
            </div>
        @endif
    </div>
</div>

@if (! $isLeaf)
    <div x-cloak x-show="open">
        @foreach ($category->childrenRecursive as $child)
            @include(
                'filament.pages.partials.financial-category-node',
                [
                    'category' => $child,
                    'level' => $level + 1,
                    'totalRevenue' => $totalRevenue,
                ]
            )
        @endforeach
    </div>
@endif
</div>
