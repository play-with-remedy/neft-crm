<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EveningType extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function evenings(): HasMany
    {
        return $this->hasMany(Evening::class);
    }
}