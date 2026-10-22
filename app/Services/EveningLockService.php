<?php

namespace App\Services;

use App\Models\Evening;
use App\Models\EveningLockPeriod;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class EveningLockService
{
    /** @var Collection<int, EveningLockPeriod> | null */
    private ?Collection $periods = null;

    public function isLocked(Evening $evening): bool
    {
        return filled($evening->locked_at) || $this->periodFor($evening->played_at) !== null;
    }

    public function isDateLocked(CarbonInterface $date): bool
    {
        return $this->periodFor($date) !== null;
    }

    public function periodFor(?CarbonInterface $date): ?EveningLockPeriod
    {
        if ($date === null) {
            return null;
        }

        $day = $date->toDateString();

        return $this->periods()
            ->first(fn (EveningLockPeriod $period): bool => $period->starts_on->toDateString() <= $day
                && $period->ends_on->toDateString() >= $day);
    }

    public function description(Evening $evening): ?string
    {
        if (filled($evening->locked_at)) {
            return filled($evening->lock_reason)
                ? 'Индивидуальная блокировка: '.$evening->lock_reason
                : 'Вечер заблокирован индивидуально';
        }

        $period = $this->periodFor($evening->played_at);

        if ($period === null) {
            return null;
        }

        $range = $period->starts_on->format('d.m.Y').'–'.$period->ends_on->format('d.m.Y');

        return filled($period->reason)
            ? "Закрытый период {$range}: {$period->reason}"
            : "Закрытый период {$range}";
    }

    public function forgetPeriods(): void
    {
        $this->periods = null;
    }

    /** @return Collection<int, EveningLockPeriod> */
    private function periods(): Collection
    {
        return $this->periods ??= EveningLockPeriod::query()
            ->orderByDesc('starts_on')
            ->get();
    }
}
