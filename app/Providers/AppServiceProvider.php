<?php

namespace App\Providers;

use App\Models\EveningParticipant;
use App\Services\AutumnCaseService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        EveningParticipant::created(function (EveningParticipant $participation): void {
            app(AutumnCaseService::class)->processParticipation($participation);
        });
    }
}
