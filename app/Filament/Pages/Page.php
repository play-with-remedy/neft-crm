<?php

namespace App\Filament\Pages;

use App\Support\FilamentAccess;
use Filament\Pages\Page as BasePage;

abstract class Page extends BasePage
{
    public static function canAccess(): bool
    {
        return FilamentAccess::canAccessNavigationGroup(static::getNavigationGroup());
    }
}
