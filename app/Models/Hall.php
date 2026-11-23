<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\ValidationException;

class Hall extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (self $hall): void {
            if ($hall->eveningVenues()->exists()) {
                throw ValidationException::withMessages(['halls' => 'Этот зал используется в вечерах и не может быть удалён.']);
            }
        });
    }

    protected $fillable = ['location_id', 'name'];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function eveningVenues(): BelongsToMany
    {
        return $this->belongsToMany(EveningVenue::class, 'evening_venue_hall');
    }
}
