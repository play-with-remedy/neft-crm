<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EveningVenue extends Model
{
    protected $fillable = ['evening_id', 'location_id'];

    public function evening(): BelongsTo
    {
        return $this->belongsTo(Evening::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function halls(): BelongsToMany
    {
        return $this->belongsToMany(Hall::class, 'evening_venue_hall')->orderBy('halls.id');
    }
}
