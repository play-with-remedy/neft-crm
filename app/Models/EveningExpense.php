<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EveningExpense extends Model
{
    protected $fillable = [
        'evening_id',
        'expense_category_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function evening(): BelongsTo
    {
        return $this->belongsTo(Evening::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}