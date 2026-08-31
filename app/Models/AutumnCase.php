<?php

namespace App\Models;

use App\Enums\AutumnCaseStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutumnCase extends Model
{
    protected $fillable = [
        'autumn_campaign_id',
        'player_id',
        'number',
        'started_at',
        'deadline_at',
        'qualified_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'date',
        'deadline_at' => 'date',
        'qualified_at' => 'date',
        'completed_at' => 'date',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AutumnCampaign::class, 'autumn_campaign_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function participations(): HasMany
    {
        return $this->hasMany(EveningParticipant::class);
    }

    public function getProgressAttribute(): int
    {
        return $this->participations()
            ->where('is_autumn_reward', false)
            ->count();
    }

    public function getStatusAttribute(): AutumnCaseStatus
    {
        return $this->statusAt(today());
    }

    public function statusAt(CarbonInterface $date): AutumnCaseStatus
    {
        if ($this->completed_at) {
            return AutumnCaseStatus::Completed;
        }

        if ($this->qualified_at) {
            return $date->gt($this->campaign->ends_at)
                ? AutumnCaseStatus::RewardExpired
                : AutumnCaseStatus::RewardAvailable;
        }

        return $date->gt($this->deadline_at)
            ? AutumnCaseStatus::Expired
            : AutumnCaseStatus::InProgress;
    }
}
