<?php

namespace App\Support;

use App\Models\AutumnCampaign;
use Livewire\Attributes\Url;

trait SelectsSeasonalCampaign
{
    #[Url(as: 'campaign')]
    public ?int $campaignId = null;

    protected function selectDefaultCampaign(): void
    {
        $this->campaignId ??= AutumnCampaign::defaultId();
    }

    public function getCampaignOptions(): array
    {
        return AutumnCampaign::query()->orderByDesc('starts_at')->pluck('name', 'id')
            ->map(fn (string $name): string => preg_replace('/\s+\d{4}(?:\s*[–—-]\s*\d{4})?\s*$/u', '', $name) ?? $name)
            ->all();
    }
}
