<?php

namespace App\Models;

use App\Services\AutumnCaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EveningParticipantPayment extends Model
{
    protected $fillable = [
        'evening_participant_id',
        'payment_type_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $payment): void {
            $participant = $payment->participant;

            if (! $participant) {
                return;
            }

            $participant->syncPaymentSummary();
            app(AutumnCaseService::class)->processParticipation($participant);
        });
        static::deleted(fn (self $payment) => $payment->participant?->syncPaymentSummary());
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(EveningParticipant::class, 'evening_participant_id');
    }

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }
}
