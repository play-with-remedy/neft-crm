<?php

namespace App\Services;

use App\Enums\AutumnCaseStatus;
use App\Models\AutumnCampaign;
use App\Models\AutumnCase;
use App\Models\EveningParticipant;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class AutumnCaseService
{
    public const REQUIRED_VISITS = 5;

    public const DEADLINE_DAYS = 30;

    public const MINIMUM_QUALIFYING_PAYMENT = 30;

    public function processParticipation(EveningParticipant $participation): void
    {
        DB::transaction(function () use ($participation): void {
            $participation = EveningParticipant::query()
                ->with('evening')
                ->lockForUpdate()
                ->findOrFail($participation->getKey());

            if ($participation->autumn_case_id !== null || ! $participation->evening) {
                return;
            }

            $visitDate = $participation->evening->played_at->startOfDay();
            $campaign = $this->campaignFor($visitDate);

            if (! $campaign) {
                return;
            }

            // Locking the campaign serializes case-number and progress changes for
            // visits that may be saved nearly simultaneously.
            $campaign = AutumnCampaign::query()
                ->lockForUpdate()
                ->findOrFail($campaign->getKey());

            $case = AutumnCase::query()
                ->with('campaign')
                ->where('autumn_campaign_id', $campaign->getKey())
                ->where('player_id', $participation->player_id)
                ->orderByDesc('number')
                ->lockForUpdate()
                ->first();

            if ($case?->statusAt($visitDate) === AutumnCaseStatus::RewardAvailable) {
                $this->redeemReward($case, $participation, $visitDate);

                return;
            }

            if ((float) $participation->paid_amount < self::MINIMUM_QUALIFYING_PAYMENT) {
                return;
            }

            if (! $case || $case->statusAt($visitDate) !== AutumnCaseStatus::InProgress) {
                $case = $this->openCase($campaign, $participation, $visitDate);
            }

            $visitNumber = $case->participations()
                ->where('is_autumn_reward', false)
                ->count() + 1;

            $participation->updateQuietly([
                'autumn_case_id' => $case->getKey(),
                'autumn_case_visit_number' => $visitNumber,
                'is_autumn_reward' => false,
            ]);

            if ($visitNumber >= self::REQUIRED_VISITS) {
                $case->update([
                    'qualified_at' => $visitDate->toDateString(),
                ]);
            }
        });
    }

    private function campaignFor(CarbonInterface $visitDate): ?AutumnCampaign
    {
        return AutumnCampaign::query()
            ->where('is_active', true)
            ->whereDate('starts_at', '<=', $visitDate->toDateString())
            ->whereDate('ends_at', '>=', $visitDate->toDateString())
            ->orderByDesc('starts_at')
            ->first();
    }

    private function openCase(
        AutumnCampaign $campaign,
        EveningParticipant $participation,
        CarbonInterface $visitDate,
    ): AutumnCase {
        $lastNumber = AutumnCase::query()
            ->where('autumn_campaign_id', $campaign->getKey())
            ->where('player_id', $participation->player_id)
            ->max('number');

        $deadline = $visitDate->copy()->addDays(self::DEADLINE_DAYS);

        if ($deadline->gt($campaign->ends_at)) {
            $deadline = $campaign->ends_at->copy();
        }

        return AutumnCase::query()->create([
            'autumn_campaign_id' => $campaign->getKey(),
            'player_id' => $participation->player_id,
            'number' => ((int) $lastNumber) + 1,
            'started_at' => $visitDate->toDateString(),
            'deadline_at' => $deadline->toDateString(),
        ]);
    }

    private function redeemReward(
        AutumnCase $case,
        EveningParticipant $participation,
        CarbonInterface $visitDate,
    ): void {
        $participation->updateQuietly([
            'autumn_case_id' => $case->getKey(),
            'autumn_case_visit_number' => null,
            'is_autumn_reward' => true,
        ]);

        $case->update([
            'completed_at' => $visitDate->toDateString(),
        ]);
    }
}
