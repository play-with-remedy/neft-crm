@once
    <style>
        .seasonal-campaign-select {
            display: grid;
            gap: 8px;
            width: 100%;
            max-width: 360px;
            min-width: 0;
        }

    </style>
@endonce

<div class="seasonal-campaign-select">
    <x-filament::input.wrapper>
        <x-filament::input.select id="seasonal-campaign" aria-label="Сезон" wire:model.live="campaignId" wire:loading.attr="disabled" wire:target="campaignId">
            @forelse ($this->getCampaignOptions() as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @empty
                <option value="">Кампаний пока нет</option>
            @endforelse
        </x-filament::input.select>
    </x-filament::input.wrapper>
</div>
