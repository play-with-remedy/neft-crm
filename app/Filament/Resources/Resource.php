<?php

namespace App\Filament\Resources;

use App\Support\FilamentAccess;
use Filament\Resources\Resource as BaseResource;

abstract class Resource extends BaseResource
{
    public static function canAccess(): bool
    {
        return FilamentAccess::canAccessNavigationGroup(static::getNavigationGroup())
            && parent::canAccess();
    }
}
