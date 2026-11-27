<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Locations\Pages\ListLocations;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Collection;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->favicon(asset('favicon.svg'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => view('filament.partials.date-picker-styles'))
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => view('filament.partials.select-styles'))
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => view('filament.partials.evening-payment-styles'))
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => view('filament.partials.location-hall-styles'), scopes: ListLocations::class)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->bootUsing(function (Panel $panel): void {
                collect([...$panel->getPages(), ...$panel->getResources()])
                    ->filter(fn (string $class): bool => in_array($class::getNavigationGroup(), ['Служебные данные', 'Отчеты'], true))
                    ->groupBy(fn (string $class): string => $class::getNavigationGroup())
                    ->each(function (Collection $classes): void {
                        $classes->sortBy(fn (string $class): string => mb_strtolower($class::getNavigationLabel()))
                            ->values()
                            ->each(fn (string $class, int $index) => $class::navigationSort($index));
                    });
            })
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigationGroups([
                'Клуб',
                'Продажи',
                'Отчеты',
                'Синхронизация',
                'Системная таблица',
                'Служебные данные',
            ]);
    }
}
