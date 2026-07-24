<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialPeriodValue extends Model
{
    protected $fillable = [
        'period',
        'corporate_revenue',
    ];

    protected $casts = [
        'period' => 'date',
        'corporate_revenue' => 'decimal:2',
    ];
}
