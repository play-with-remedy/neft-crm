<?php

namespace App\Models;

use App\Services\EveningLockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EveningLockPeriod extends Model
{
    protected $fillable = [
        'starts_on',
        'ends_on',
        'reason',
        'locked_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (): mixed => app(EveningLockService::class)->forgetPeriods());
        static::deleted(fn (): mixed => app(EveningLockService::class)->forgetPeriods());
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }
}
