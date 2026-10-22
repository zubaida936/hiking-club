<?php

namespace App\Providers;

use App\Domain\Events\Difficulty\DifficultyCalculator;
use App\Domain\Events\Events\EventPublished;
use App\Domain\Identity\Enums\Role;
use App\Domain\Newsletter\Listeners\AnnounceNewEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event as EventBus;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DifficultyCalculator::class, fn () => DifficultyCalculator::fromConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Catch lazy loading, silently discarded attributes, etc. outside production.
        Model::shouldBeStrict(! $this->app->isProduction());

        Gate::before(fn (User $user) => $user->hasRole(Role::SuperAdmin->value) ? true : null);

        // Domain listeners live in their module, outside app/Listeners, so register them here.
        EventBus::listen(EventPublished::class, AnnounceNewEvent::class);
    }
}
