<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateUsage extends Model
{
    protected $fillable = [
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'used_at' => 'date',
        ];
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }
}
