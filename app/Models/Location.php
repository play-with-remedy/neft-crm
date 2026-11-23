<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = ['name', 'address'];

    public function halls(): HasMany
    {
        return $this->hasMany(Hall::class)->orderBy('id');
    }

    public function eveningVenues(): HasMany
    {
        return $this->hasMany(EveningVenue::class);
    }
}
