<?php

namespace App\Rules;

use App\Services\EveningLockService;
use App\Support\FilamentAccess;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

class UnlockedEveningDate implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (FilamentAccess::isSuperAdmin() || blank($value)) {
            return;
        }

        try {
            $date = CarbonImmutable::parse($value);
        } catch (Throwable) {
            return;
        }

        if (app(EveningLockService::class)->isDateLocked($date)) {
            $fail('Этот период закрыт. Выберите другую дату или обратитесь к суперадмину.');
        }
    }
}
