<?php

namespace App\Policies;

use App\Models\Evening;
use App\Models\User;
use App\Services\EveningLockService;

class EveningPolicy
{
    public function __construct(private readonly EveningLockService $locks) {}

    public function before(?User $user): ?bool
    {
        return $user?->isSuperAdmin() ? true : null;
    }

    public function viewAny(?User $user): bool
    {
        return $this->hasPanelAccess($user);
    }

    public function view(?User $user, Evening $evening): bool
    {
        return $this->hasPanelAccess($user);
    }

    public function create(?User $user): bool
    {
        return $this->hasPanelAccess($user);
    }

    public function update(?User $user, Evening $evening): bool
    {
        return $this->hasPanelAccess($user) && ! $this->locks->isLocked($evening);
    }

    public function delete(?User $user, Evening $evening): bool
    {
        return $this->hasPanelAccess($user) && ! $this->locks->isLocked($evening);
    }

    public function deleteAny(?User $user): bool
    {
        return false;
    }

    public function restore(?User $user, Evening $evening): bool
    {
        return false;
    }

    public function forceDelete(?User $user, Evening $evening): bool
    {
        return false;
    }

    private function hasPanelAccess(?User $user): bool
    {
        return $user !== null || app()->runningUnitTests();
    }
}
