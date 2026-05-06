<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    protected $fillable = [
        'name',
    ];

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }
}