<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Player extends Model
{
    public const MANUAL_ACTIVITY_STATUS_SEASON_PLAYER = 'season_player';

    public const CLUB_PLAYER_VISITS_THRESHOLD = 20;

    protected $fillable = [
        'nickname',
        'first_name',
        'last_name',
        'gender',
        'birth_day',
        'birth_month',
        'birth_year',
        'phone',
        'telegram',
        'source_id',
        'first_visit_at',
        'first_host_id',
        'notes',
        'manual_activity_status',
    ];

    /** @return array<string, string> */
    public static function manualActivityStatusOptions(): array
    {
        return [
            self::MANUAL_ACTIVITY_STATUS_SEASON_PLAYER => 'Игрок сезона',
        ];
    }

    public function getManualActivityStatusLabelAttribute(): ?string
    {
        return self::manualActivityStatusOptions()[$this->manual_activity_status] ?? null;
    }

    public function getActivityStatusLabelAttribute(): string
    {
        return $this->resolveActivityStatusLabel((int) ($this->recent_visits_count ?? 0));
    }

    public function resolveActivityStatusLabel(int $recentVisitsCount): string
    {
        if ($this->manual_activity_status_label !== null) {
            return $this->manual_activity_status_label;
        }

        return $recentVisitsCount >= self::CLUB_PLAYER_VISITS_THRESHOLD ? 'Клубный игрок' : 'Гость клуба';
    }

    protected $casts = [
        'first_visit_at' => 'date',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function firstHost(): BelongsTo
    {
        return $this->belongsTo(Host::class, 'first_host_id');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(EveningParticipant::class);
    }

    public function autumnCases(): HasMany
    {
        return $this->hasMany(AutumnCase::class);
    }

    public function latestAutumnCase(): HasOne
    {
        return $this->hasOne(AutumnCase::class)->latestOfMany();
    }

    public function getGenderLabelAttribute(): string
    {
        return match ($this->gender) {
            'male' => 'Мужской',
            'female' => 'Женский',
            default => '-',
        };
    }

    private function normalizeGender(?string $value): ?string
{
    $value = trim((string) $value);

    return match ($value) {
        'М', 'м' => 'male',
        'Ж', 'ж' => 'female',
        default => null,
    };
}
}
