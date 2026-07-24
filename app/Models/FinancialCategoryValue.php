<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialCategoryValue extends Model
{
    protected $fillable = [
        'financial_category_id',
        'period',
        'amount',
        'details',
    ];

    protected $casts = [
        'period' => 'date',
        'amount' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            FinancialCategory::class,
            'financial_category_id'
        );
    }
}