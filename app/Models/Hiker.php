<?php

namespace App\Models;

use App\Domain\Loyalty\Enums\RankTier;
use App\Domain\Registrations\Enums\Attendance;
use App\Domain\Registrations\Enums\RegistrationStatus;
use Database\Factories\HikerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A person who books hikes, identified by phone number. Owns bookings and points.
 *
 * @property int $id
 * @property string $phone normalized: "+" and digits
 * @property string $name
 * @property ?string $email
 * @property ?int $user_id
 */
class Hiker extends Model
{
    /** @use HasFactory<HikerFactory> */
    use HasFactory;

    /**
     * Written only by domain actions with explicit attribute arrays.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * @return HasMany<EventRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * @return HasMany<PointTransaction, $this>
     */
    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Adds total_points and hikes_completed.
     *
     * @param  Builder<self>  $query
     */
    public function scopeWithPointTotals(Builder $query): void
    {
        $query->withSum('pointTransactions as total_points', 'points')
            ->withCount(['registrations as hikes_completed' => fn ($q) => $q
                ->where('status', RegistrationStatus::Confirmed)
                ->where('attendance', Attendance::Attended)]);
    }

    public function totalPoints(): int
    {
        return (int) ($this->attributes['total_points'] ?? $this->pointTransactions()->sum('points'));
    }

    public function tier(): RankTier
    {
        return RankTier::forPoints($this->totalPoints());
    }

    /**
     * Points still needed for the next tier; null at the top tier.
     */
    public function pointsToNextTier(): ?int
    {
        $next = $this->tier()->next();

        return $next ? $next->minPoints() - $this->totalPoints() : null;
    }

    /**
     * Km of completed (attended) hikes; uses total_distance_km from the Leaderboard query when present.
     */
    public function totalDistanceKm(): float
    {
        if (array_key_exists('total_distance_km', $this->attributes)) {
            return (float) $this->attributes['total_distance_km'];
        }

        return (float) $this->registrations()
            ->join('events', 'events.id', '=', 'event_registrations.event_id')
            ->where('event_registrations.status', RegistrationStatus::Confirmed)
            ->where('event_registrations.attendance', Attendance::Attended)
            ->sum('events.distance_km');
    }
}
