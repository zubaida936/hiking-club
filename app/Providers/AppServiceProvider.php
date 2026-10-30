<?php

namespace App\Providers;

use App\Domain\Events\Difficulty\DifficultyCalculator;
use App\Domain\Events\Events\EventCancelled;
use App\Domain\Events\Events\EventPublished;
use App\Domain\Events\Events\EventRescheduled;
use App\Domain\Events\Listeners\AnnounceNewDate;
use App\Domain\Identity\Enums\Role;
use App\Domain\Loyalty\Listeners\SyncPointsForRegistration;
use App\Domain\Members\Codes\CodeChannel;
use App\Domain\Newsletter\Listeners\AnnounceNewEvent;
use App\Domain\Registrations\Events\AttendanceMarked;
use App\Domain\Registrations\Events\RegistrationChanged;
use App\Domain\Registrations\Listeners\CancelBookingsOfCancelledEvent;
use App\Domain\Rewards\PerkCode;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event as EventBus;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DifficultyCalculator::class, fn () => DifficultyCalculator::fromConfig());
        $this->app->singleton(PerkCode::class, fn () => PerkCode::fromConfig());

        // config/members.php picks how one-time codes reach members (mail today, SMS/WhatsApp later).
        $this->app->bind(CodeChannel::class, fn ($app) => $app->make(config('members.code_channels.'.config('members.code_channel'))));

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Catch lazy loading, silently discarded attributes, etc. outside production.
        Model::shouldBeStrict(! $this->app->isProduction());

        Gate::before(fn (User $user) => $user->hasRole(Role::SuperAdmin->value) ? true : null);

        // Default limit for the app's API calls; sensitive endpoints add their own stricter throttle.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        // Domain listeners live in their module, outside app/Listeners, so register them here.
        EventBus::listen(EventPublished::class, AnnounceNewEvent::class);
        EventBus::listen([AttendanceMarked::class, RegistrationChanged::class], SyncPointsForRegistration::class);
        EventBus::listen(EventCancelled::class, CancelBookingsOfCancelledEvent::class);
        EventBus::listen(EventRescheduled::class, AnnounceNewDate::class);
    }
}
