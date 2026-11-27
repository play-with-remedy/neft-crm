<?php

namespace App\Services;

use App\Models\AutumnCampaign;
use App\Models\AutumnCase;
use App\Models\Evening;
use App\Models\EveningParticipant;
use App\Models\Player;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

class SeasonalCaseService
{
    private bool $batching = false;

    /** @var array<int, array{evenings: array<int, int>, dates: array<string, string>}> */
    private array $pending = [];

    /** Save the whole form and rebuild each affected player/season once, atomically. */
    public function batch(callable $callback): mixed
    {
        if ($this->batching) {
            return $callback();
        }

        $previous = $this->pending;
        $this->batching = true;

        try {
            return DB::transaction(function () use ($callback) {
                $result = $callback();
                $this->flush();

                return $result;
            });
        } catch (Throwable $exception) {
            $this->pending = $previous;

            throw $exception;
        } finally {
            $this->batching = false;
        }
    }

    public function participationChanged(EveningParticipant $participation): void
    {
        $this->batch(function () use ($participation): void {
            $this->schedule((int) $participation->player_id, (int) $participation->evening_id);
            $this->schedule(
                (int) $participation->getRawOriginal('player_id'),
                (int) $participation->getRawOriginal('evening_id'),
            );
        });
    }

    public function eveningChanged(Evening $evening, array $playerIds): void
    {
        $this->batch(function () use ($evening, $playerIds): void {
            foreach ($playerIds as $playerId) {
                foreach ([$evening->getRawOriginal('played_at'), $evening->played_at] as $date) {
                    if ($date !== null) {
                        $this->schedule((int) $playerId, date: CarbonImmutable::parse($date)->toDateString());
                    }
                }
            }
        });
    }

    private function schedule(int $playerId, ?int $eveningId = null, ?string $date = null): void
    {
        if (! $playerId) {
            return;
        }

        $this->pending[$playerId] ??= ['evenings' => [], 'dates' => []];

        if ($eveningId) {
            $this->pending[$playerId]['evenings'][$eveningId] = $eveningId;
        }

        if ($date !== null) {
            $this->pending[$playerId]['dates'][$date] = $date;
        }
    }

    private function flush(): void
    {
        if ($this->pending === []) {
            return;
        }

        $pending = $this->pending;
        $this->pending = [];
        ksort($pending);
        $eveningIds = collect($pending)->flatMap(fn (array $target): array => $target['evenings'])->unique();
        $dates = Evening::query()->whereKey($eveningIds)->pluck('played_at', 'id');
        $campaigns = AutumnCampaign::query()->where('is_active', true)->orderBy('id')->get();

        foreach ($pending as $playerId => $target) {
            $playerDates = collect($target['evenings'])
                ->map(fn (int $id) => $dates->get($id))
                ->filter()
                ->map(fn ($date): string => CarbonImmutable::parse($date)->toDateString())
                ->merge($target['dates'])->unique();

            foreach ($campaigns as $campaign) {
                if ($playerDates->contains(fn (string $date): bool => $date >= $campaign->starts_at->toDateString()
                    && $date <= $campaign->ends_at->toDateString())) {
                    $this->recalculatePlayerCampaign($playerId, $campaign->id);
                }
            }
        }
    }

    public function recalculatePlayerCampaign(int $playerId, int $campaignId): void
    {
        DB::transaction(function () use ($playerId, $campaignId): void {
            // Serialize changes for this player, rather than blocking the whole campaign.
            if (! Player::query()->whereKey($playerId)->lockForUpdate()->first(['id'])) {
                return;
            }

            $campaign = AutumnCampaign::query()->findOrFail($campaignId);
            $existing = $campaign->cases()->where('player_id', $playerId)->lockForUpdate()->get()->keyBy('number');
            $visits = EveningParticipant::query()
                ->join('evenings', 'evenings.id', '=', 'evening_participants.evening_id')
                ->where('evening_participants.player_id', $playerId)
                ->whereBetween('evenings.played_at', [
                    $campaign->starts_at->copy()->startOfDay(),
                    $campaign->ends_at->copy()->endOfDay(),
                ])
                ->orderBy('evenings.played_at')
                ->orderBy('evening_participants.id')
                ->lockForUpdate()
                ->get(['evening_participants.*', 'evenings.played_at']);

            $cases = [];
            $assignments = [];
            $number = 0;

            foreach ($visits as $visit) {
                $date = CarbonImmutable::parse($visit->played_at)->startOfDay();
                $day = $date->toDateString();
                $amount = (float) $visit->paid_amount;

                if ($number && $cases[$number]['qualified_at'] !== null && $cases[$number]['completed_at'] === null) {
                    if (! $campaign->reward_requires_zero_payment || $amount === 0.0) {
                        $cases[$number]['completed_at'] = $day;
                        $assignments[$visit->id] = [$number, null, true];
                    }

                    // Paid visits while a winter reward is pending do not spend it
                    // or count toward a second case before the first one is closed.
                    continue;
                }

                if ($amount < (float) $campaign->minimum_qualifying_payment) {
                    continue;
                }

                if (! $number || $cases[$number]['completed_at'] !== null || $day > $cases[$number]['deadline_at']) {
                    $number++;
                    $deadline = $date->addDays($campaign->deadline_days)->min($campaign->ends_at);
                    $cases[$number] = [
                        'started_at' => $day,
                        'deadline_at' => $deadline->toDateString(),
                        'qualified_at' => null,
                        'completed_at' => null,
                        'progress' => 0,
                    ];
                }

                $progress = ++$cases[$number]['progress'];
                $assignments[$visit->id] = [$number, $progress, false];

                if ($progress >= $campaign->required_visits) {
                    $cases[$number]['qualified_at'] = $day;
                }
            }

            $caseIds = [];

            foreach ($cases as $caseNumber => $attributes) {
                unset($attributes['progress']);
                $case = $existing->get($caseNumber) ?? new AutumnCase([
                    'autumn_campaign_id' => $campaignId,
                    'player_id' => $playerId,
                    'number' => $caseNumber,
                ]);
                $case->fill($attributes);

                if (! $case->exists || $case->isDirty()) {
                    $case->save();
                }

                $caseIds[$caseNumber] = $case->id;
            }

            // Include old links outside the season (a visit may have moved dates/player).
            $linked = EveningParticipant::query()->whereIn('autumn_case_id', $existing->modelKeys())->lockForUpdate()->get();
            $changes = [];
            $detachIds = [];

            foreach ($linked->keyBy('id')->union($visits->keyBy('id')) as $visit) {
                $assignment = $assignments[$visit->id] ?? null;
                $desired = [
                    'autumn_case_id' => $assignment === null ? null : $caseIds[$assignment[0]],
                    'autumn_case_visit_number' => $assignment[1] ?? null,
                    'is_autumn_reward' => $assignment[2] ?? false,
                ];

                if (($visit->autumn_case_id === null ? null : (int) $visit->autumn_case_id) === $desired['autumn_case_id']
                    && ($visit->autumn_case_visit_number === null ? null : (int) $visit->autumn_case_visit_number) === $desired['autumn_case_visit_number']
                    && (bool) $visit->is_autumn_reward === $desired['is_autumn_reward']) {
                    continue;
                }

                $changes[$visit->id] = $desired;

                if ($visit->autumn_case_id !== null) {
                    $detachIds[] = $visit->id;
                }
            }

            // Release old visit numbers together before assigning shifted numbers.
            if ($detachIds !== []) {
                DB::table('evening_participants')->whereIn('id', $detachIds)->update([
                    'autumn_case_id' => null, 'autumn_case_visit_number' => null, 'is_autumn_reward' => false,
                ]);
            }

            foreach ($changes as $id => $attributes) {
                DB::table('evening_participants')->where('id', $id)->update($attributes);
            }

            $obsoleteIds = $existing->whereNotIn('id', $caseIds)->modelKeys();

            if ($obsoleteIds !== []) {
                AutumnCase::query()->whereKey($obsoleteIds)->delete();
            }
        });
    }
}
