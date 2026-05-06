<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
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
    ];

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