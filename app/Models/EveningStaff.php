<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EveningStaff extends Model
{
    protected $table = 'evening_staff';

    protected $fillable = [
        'evening_id',
        'host_id',
        'role',
        'salary',
    ];

    protected $casts = [
        'salary' => 'integer',
    ];

    public function evening(): BelongsTo
    {
        return $this->belongsTo(Evening::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(Host::class);
    }
}