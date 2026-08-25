<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceAdvertisingExpense extends Model
{
    protected $fillable = [
        'source_id',
        'month',
        'amount',
    ];

    protected $casts = [
        'month' => 'date',
        'amount' => 'decimal:2',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
