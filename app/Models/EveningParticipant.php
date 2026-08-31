<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EveningParticipant extends Model
{
    protected $fillable = [
        'evening_id',
        'player_id',
        'autumn_case_id',
        'autumn_case_visit_number',
        'is_autumn_reward',
        'paid_amount',
        'payment_type_id',
        'is_new_player',
        'is_full_payment',
        'note',
    ];

    protected $casts = [
        'is_new_player' => 'boolean',
        'is_full_payment' => 'boolean',
        'is_autumn_reward' => 'boolean',
    ];

    public function evening(): BelongsTo
    {
        return $this->belongsTo(Evening::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function autumnCase(): BelongsTo
    {
        return $this->belongsTo(AutumnCase::class);
    }
}
