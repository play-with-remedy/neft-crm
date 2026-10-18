<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'received_at',
        'name',
        'phone',
        'telegram',
        'source_channel',
        'stage',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'date',
        ];
    }
}
