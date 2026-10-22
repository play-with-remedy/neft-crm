<?php

namespace App\Support;

use App\Models\User;
use BackedEnum;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

final class FilamentAccess
{
    private const ADMIN_NAVIGATION_GROUPS = [
        'Клуб',
        'Продажи',
    ];

    public static function canAccessNavigationGroup(string | UnitEnum | null $group): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return app()->runningUnitTests();
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($group instanceof BackedEnum) {
            $group = $group->value;
        } elseif ($group instanceof UnitEnum) {
            $group = $group->name;
        }

        return in_array($group, self::ADMIN_NAVIGATION_GROUPS, true);
    }

    public static function isSuperAdmin(): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return app()->runningUnitTests();
        }

        return $user->isSuperAdmin();
    }
}
