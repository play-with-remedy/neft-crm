<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evening extends Model
{
    protected $fillable = [
        'played_at',
        'evening_type_id',
        'project_id',
        'other_expenses',
    ];

    protected $casts = [
        'played_at' => 'datetime',
        'other_expenses' => 'integer',
    ];

    public function eveningType(): BelongsTo
    {
        return $this->belongsTo(EveningType::class, 'evening_type_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(EveningParticipant::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(EveningStaff::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(EveningExpense::class);
    }

    public function getTotalPaidAmountAttribute(): int
    {
        return (int) $this->participants()->sum('paid_amount');
    }

    public function getStaffSalaryTotalAttribute(): int
    {
        return (int) $this->staff()->sum('salary');
    }

    public function getExpensesTotalAttribute(): int
    {
        return (int) $this->expenses()->sum('amount');
    }

    public function getProfitAttribute(): int
    {
        return $this->total_paid_amount
            - $this->expenses_total
            - $this->staff_salary_total;
    }

    public function getPlayersCountAttribute(): int
    {
        return $this->participants()->count();
    }

    public function getNewPlayersCountAttribute(): int
    {
        return $this->participants()
            ->where('is_new_player', true)
            ->count();
    }

    public function getFullPaymentPlayersCountAttribute(): int
    {
        return $this->participants()
            ->where('is_full_payment', true)
            ->count();
    }

    public function getPaymentsByTypeAttribute(): array
    {
        return PaymentType::query()
            ->get()
            ->map(fn (PaymentType $type) => [
                'name' => $type->type,
                'amount' => (int) $this->participants()
                    ->where('payment_type_id', $type->id)
                    ->sum('paid_amount'),
            ])
            ->toArray();
    }
}