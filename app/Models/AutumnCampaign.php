<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutumnCampaign extends Model
{
    protected $fillable = [
        'name',
        'starts_at',
        'ends_at',
        'is_active',
        'required_visits',
        'deadline_days',
        'minimum_qualifying_payment',
        'reward_requires_zero_payment',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean',
        'required_visits' => 'integer',
        'deadline_days' => 'integer',
        'minimum_qualifying_payment' => 'decimal:2',
        'reward_requires_zero_payment' => 'boolean',
    ];

    public static function defaultId(): ?int
    {
        $today = Evening::clubNow()->toDateString();

        return static::query()
            ->orderByRaw('CASE WHEN starts_at <= ? AND ends_at >= ? THEN 0 WHEN starts_at > ? THEN 1 ELSE 2 END', [
                $today, $today, $today,
            ])
            ->orderByRaw('CASE WHEN starts_at > ? THEN starts_at END ASC', [$today])
            ->orderByDesc('starts_at')
            ->value('id');
    }

    public function cases(): HasMany
    {
        return $this->hasMany(AutumnCase::class);
    }
}
