<?php

namespace App\Models;

use App\Services\SeasonalCaseService;
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
            if ($payment->getRawOriginal('id') === null || $payment->wasChanged(['evening_participant_id', 'payment_type_id', 'amount'])) {
                $payment->syncParticipants();
            }
        });
        static::deleted(fn (self $payment) => $payment->syncParticipants());
    }

    private function syncParticipants(): void
    {
        app(SeasonalCaseService::class)->batch(function (): void {
            $ids = array_filter([$this->evening_participant_id, $this->getRawOriginal('evening_participant_id')]);

            foreach (EveningParticipant::query()->whereKey($ids)->get() as $participant) {
                $participant->syncPaymentSummary();

                if ($participant->wasChanged('paid_amount')) {
                    app(SeasonalCaseService::class)->participationChanged($participant);
                }
            }
        });
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
