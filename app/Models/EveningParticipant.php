<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EveningParticipant extends Model
{
    protected $fillable = [
        'evening_id',
        'player_id',
        'paid_amount',
        'payment_type_id',
        'is_new_player',
        'is_full_payment',
        'note',
    ];

    protected $casts = [
        'is_new_player' => 'boolean',
        'is_full_payment' => 'boolean',
    ];

    public function evening()
    {
        return $this->belongsTo(Evening::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }
}