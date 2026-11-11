<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EveningParticipant extends Model
{
    protected static function booted(): void
    {
        static::created(function (self $participant): void {
            if ($participant->payment_type_id === null || $participant->payments()->exists()) {
                return;
            }

            $participant->payments()->create([
                'payment_type_id' => $participant->payment_type_id,
                'amount' => (float) ($participant->paid_amount ?? 0),
            ]);
        });
    }

    protected $fillable = [
        'evening_id',
        'player_id',
        'autumn_case_id',
        'autumn_case_visit_number',
        'is_autumn_reward',
        'paid_amount',
        'payment_type_id',
        'is_new_player',
        'note',
    ];

    protected $casts = [
        'is_new_player' => 'boolean',
        'is_autumn_reward' => 'boolean',
        'paid_amount' => 'decimal:2',
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

    public function payments(): HasMany
    {
        return $this->hasMany(EveningParticipantPayment::class);
    }

    public function syncPaymentSummary(): void
    {
        $payments = $this->payments()->orderBy('id')->get();

        $this->updateQuietly([
            'payment_type_id' => $payments->first()?->payment_type_id,
            'paid_amount' => $payments->sum(fn (EveningParticipantPayment $payment): float => (float) $payment->amount),
        ]);
    }

    public function getPaymentSummaryAttribute(): string
    {
        $payments = $this->relationLoaded('payments')
            ? $this->payments
            : $this->payments()->with('paymentType')->get();

        if ($payments->isEmpty() && $this->paymentType) {
            return $this->paymentType->type.' — '.number_format((float) $this->paid_amount, 2, ',', ' ').' BYN';
        }

        return $payments
            ->map(fn (EveningParticipantPayment $payment): string => ($payment->paymentType?->type ?? '—')
                .' — '.number_format((float) $payment->amount, 2, ',', ' ').' BYN')
            ->join('; ');
    }

    public function autumnCase(): BelongsTo
    {
        return $this->belongsTo(AutumnCase::class);
    }
}
