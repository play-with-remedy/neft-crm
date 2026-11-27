<?php

namespace App\Services;

use App\Models\EveningParticipant;

/** Compatibility entry point for existing autumn integrations. */
class AutumnCaseService
{
    public const REQUIRED_VISITS = 5;

    public const DEADLINE_DAYS = 30;

    public const MINIMUM_QUALIFYING_PAYMENT = 30;

    public function processParticipation(EveningParticipant $participation): void
    {
        app(SeasonalCaseService::class)->participationChanged($participation);
    }
}
