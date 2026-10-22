<?php

namespace App\Providers;

use App\Models\Evening;
use App\Models\EveningParticipant;
use App\Policies\EveningPolicy;
use App\Services\AutumnCaseService;
use App\Services\EveningLockService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(EveningLockService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Evening::class, EveningPolicy::class);

        EveningParticipant::created(function (EveningParticipant $participation): void {
            app(AutumnCaseService::class)->processParticipation($participation);
        });
    }
}
