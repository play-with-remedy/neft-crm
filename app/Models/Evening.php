<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Evening extends Model
{
    public const CLUB_TIMEZONE = 'Europe/Minsk';

    protected $fillable = [
        'played_at',
        'evening_type_id',
        'project_id',
        'price',
        'other_expenses',
        'locked_at',
        'locked_by_user_id',
        'lock_reason',
    ];

    protected $casts = [
        'played_at' => 'datetime',
        'other_expenses' => 'decimal:2',
        'price' => 'decimal:2',
        'locked_at' => 'datetime',
    ];

    // Evening dates are stored as club-local wall time, including historical dates.
    public static function clubNow(): Carbon
    {
        return now(self::CLUB_TIMEZONE);
    }

    public function scopeOccurred(Builder $query): Builder
    {
        return $query->where('evenings.played_at', '<=', self::clubNow()->format('Y-m-d H:i:s'));
    }

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

    public function venues(): HasMany
    {
        return $this->hasMany(EveningVenue::class)->orderBy('id');
    }

    public function syncVenueSelections(array $selections): void
    {
        Validator::make(['venues' => $selections], [
            'venues' => ['array'],
            'venues.*.location_id' => ['required', 'integer', 'distinct', 'exists:locations,id'],
            'venues.*.hall_ids' => ['required', 'array', 'min:1'],
            'venues.*.hall_ids.*' => ['required', 'integer', 'exists:halls,id'],
        ], [
            'venues.*.location_id.*' => 'Выберите существующую локацию без повторений.',
            'venues.*.hall_ids.*' => 'Выберите хотя бы один зал выбранной локации.',
        ])->validate();

        DB::transaction(function () use ($selections): void {
            $locationIds = [];

            foreach ($selections as $selection) {
                $locationId = (int) $selection['location_id'];
                $hallIds = array_values(array_unique(array_map('intval', $selection['hall_ids'])));

                if (Hall::query()->where('location_id', $locationId)->whereKey($hallIds)->count() !== count($hallIds)) {
                    throw ValidationException::withMessages(['venues' => 'Выбранные залы должны принадлежать своей локации.']);
                }

                $venue = $this->venues()->firstOrCreate(['location_id' => $locationId]);
                $venue->halls()->sync($hallIds);
                $locationIds[] = $locationId;
            }

            $this->venues()->whereNotIn('location_id', $locationIds)->delete();
        });

        $this->unsetRelation('venues');
    }

    public function getVenueLabelsAttribute(): array
    {
        $this->loadMissing(['venues.location', 'venues.halls']);

        return $this->venues->map(fn (EveningVenue $venue): string => $venue->location->name.' — '.$venue->halls->pluck('name')->join('; '))->all();
    }

    public function venueExportData(): array
    {
        $this->loadMissing(['venues.location', 'venues.halls']);

        return $this->venues->map(fn (EveningVenue $venue): array => [
            'location' => $venue->location->name,
            'address' => $venue->location->address,
            'halls' => $venue->halls->pluck('name')->all(),
        ])->all();
    }

    public function staff(): HasMany
    {
        return $this->hasMany(EveningStaff::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(EveningExpense::class);
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }

    public function getTotalPaidAmountAttribute(): float
    {
        return (float) $this->participants()->sum('paid_amount');
    }

    public function getStaffSalaryTotalAttribute(): float
    {
        return (float) $this->staff()->sum('salary');
    }

    public function getExpensesTotalAttribute(): float
    {
        return (float) $this->expenses()->sum('amount');
    }

    public function getProfitAttribute(): float
    {
        return $this->total_paid_amount
            - $this->expenses_total
            - $this->staff_salary_total;
    }

    public function getPlayersCountAttribute(): int
    {
        return $this->participants()->count();
    }

    public function getPaymentsByTypeAttribute(): array
    {
        return PaymentType::query()
            ->get()
            ->map(fn (PaymentType $type) => [
                'name' => $type->type,
                'amount' => (float) EveningParticipantPayment::query()
                    ->where('payment_type_id', $type->id)
                    ->whereHas('participant', fn ($query) => $query->where('evening_id', $this->getKey()))
                    ->sum('amount'),
            ])
            ->toArray();
    }
}
