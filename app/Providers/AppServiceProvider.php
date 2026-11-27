<?php

namespace App\Providers;

use App\Models\Evening;
use App\Models\EveningParticipant;
use App\Policies\EveningPolicy;
use App\Services\EveningLockService;
use App\Services\SeasonalCaseService;
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
        $this->app->scoped(SeasonalCaseService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Evening::class, EveningPolicy::class);

        EveningParticipant::saved(function (EveningParticipant $participation): void {
            if ($participation->getRawOriginal('id') === null || $participation->wasChanged(['player_id', 'evening_id', 'paid_amount'])) {
                app(SeasonalCaseService::class)->participationChanged($participation);
            }
        });
        EveningParticipant::deleted(fn (EveningParticipant $participation) => app(SeasonalCaseService::class)->participationChanged($participation));

        Evening::updated(function (Evening $evening): void {
            if ($evening->wasChanged('played_at')) {
                app(SeasonalCaseService::class)->eveningChanged($evening, $evening->participants()->pluck('player_id')->all());
            }
        });
        // Database cascades do not fire participant model events.
        Evening::deleting(fn (Evening $evening) => $evening->load('participants'));
        Evening::deleted(fn (Evening $evening) => app(SeasonalCaseService::class)
            ->eveningChanged($evening, $evening->participants->pluck('player_id')->all()));
    }
}
