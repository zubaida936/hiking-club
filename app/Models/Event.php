<?php

namespace App\Models;

use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Events\Enums\EventStatus;
use Carbon\CarbonImmutable;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property ?string $season_tag
 * @property string $location
 * @property Carbon $starts_on
 * @property ?string $summary
 * @property ?string $description
 * @property float $distance_km
 * @property int $elevation_gain_m
 * @property int $elevation_loss_m
 * @property ?float $duration_hours
 * @property int $effort_score
 * @property DifficultyLevel $difficulty_level
 * @property int $fee_cents
 * @property int $capacity
 * @property ?Carbon $registration_closes_at
 * @property bool $waitlist_enabled
 * @property ?list<string> $included
 * @property ?list<string> $what_to_bring
 * @property ?list<string> $itinerary
 * @property ?string $cover_image_path
 * @property ?string $photo_credit
 * @property ?string $gpx_path
 * @property ?list<array{float, float}> $route_points
 * @property bool $is_xtreme
 * @property EventStatus $status
 * @property ?Carbon $published_at
 * @property ?Carbon $cancelled_at
 * @property ?string $cancellation_reason
 */
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Everything is written through the Events domain actions, which pass
     * explicit attribute arrays; nothing is filled from raw request input.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'distance_km' => 'float',
            'elevation_gain_m' => 'integer',
            'elevation_loss_m' => 'integer',
            'duration_hours' => 'float',
            'effort_score' => 'integer',
            'difficulty_level' => DifficultyLevel::class,
            'fee_cents' => 'integer',
            'capacity' => 'integer',
            'registration_closes_at' => 'datetime',
            'waitlist_enabled' => 'boolean',
            'included' => 'array',
            'what_to_bring' => 'array',
            'itinerary' => 'array',
            'route_points' => 'array',
            'is_xtreme' => 'boolean',
            'status' => EventStatus::class,
            'published_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<EventMeetingPoint, $this>
     */
    public function meetingPoints(): HasMany
    {
        return $this->hasMany(EventMeetingPoint::class)->orderBy('position');
    }

    /**
     * Messages from the organizers, newest first.
     *
     * @return HasMany<EventUpdate, $this>
     */
    public function updates(): HasMany
    {
        return $this->hasMany(EventUpdate::class)->latest('id');
    }

    /**
     * @return HasMany<EventRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', EventStatus::Published);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->whereDate('starts_on', '>=', today());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePast(Builder $query): void
    {
        $query->whereDate('starts_on', '<', today());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $query->when($term, fn (Builder $q) => $q->where(fn (Builder $q) => $q
            ->where('title', 'like', "%{$term}%")
            ->orWhere('location', 'like', "%{$term}%")));
    }

    /**
     * Events with a public page: published ones, plus cancelled ones that were public
     * before, so shared links explain the cancellation instead of returning a 404.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleOnSite(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q
            ->where('status', EventStatus::Published)
            ->orWhere(fn (Builder $q) => $q->where('status', EventStatus::Cancelled)->whereNotNull('published_at')));
    }

    /**
     * The next published event that hasn't happened yet (today counts).
     */
    public static function nextUpcoming(): ?self
    {
        return static::published()->upcoming()->orderBy('starts_on')->orderBy('id')->first();
    }

    public function isUpcoming(): bool
    {
        return $this->starts_on->isToday() || $this->starts_on->isFuture();
    }

    public function isPublished(): bool
    {
        return $this->status === EventStatus::Published;
    }

    public function feeAmount(): string
    {
        return number_format($this->fee_cents / 100, 2, '.', '');
    }

    public function formattedFee(): string
    {
        return $this->fee_cents === 0 ? 'Free' : '$'.rtrim(rtrim($this->feeAmount(), '0'), '.');
    }

    /**
     * "12.5 km", "8 km": one decimal at most, no trailing ".0".
     */
    public function formattedDistance(): string
    {
        return rtrim(rtrim(number_format($this->distance_km, 1), '0'), '.').' km';
    }

    public function isCancelled(): bool
    {
        return $this->status === EventStatus::Cancelled;
    }

    /**
     * Same rule as scopeVisibleOnSite(), for an already loaded event.
     */
    public function isVisibleOnSite(): bool
    {
        return $this->isPublished() || ($this->isCancelled() && $this->published_at !== null);
    }

    /**
     * Seats taken by confirmed and pending registrations. Uses a loaded
     * withCount(['registrations as active_registrations_count' => ...]) when present.
     */
    public function seatsTaken(): int
    {
        return (int) ($this->attributes['active_registrations_count'] ?? $this->registrations()->active()->count());
    }

    public function seatsLeft(): int
    {
        return max(0, $this->capacity - $this->seatsTaken());
    }

    /**
     * True from the hike day on, in club time (for check-in / attendance).
     */
    public function hikeDayReached(): bool
    {
        return today(config('site.timezone'))->toDateString() >= $this->starts_on->toDateString();
    }

    public function isSoldOut(): bool
    {
        return $this->seatsLeft() === 0;
    }

    /**
     * Until when a paid booking can be cancelled online: midnight (club time) starting the day that is
     * config site.booking.paid_cancellation_days before the hike day (2 → a Saturday hike until Wednesday 23:59).
     */
    public function paidCancellationDeadline(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->starts_on->toDateString(), config('site.timezone'))
            ->subDays(max(0, (int) config('site.booking.paid_cancellation_days')))
            ->utc();
    }

    /**
     * When bookings close: the organizer's deadline, or midnight (club time) at the start of the hike day.
     */
    public function registrationDeadline(): CarbonImmutable
    {
        return $this->registration_closes_at?->toImmutable()
            ?? CarbonImmutable::parse($this->starts_on->toDateString(), config('site.timezone'))->utc();
    }

    /**
     * Published events take bookings (or waitlist entries) until the deadline.
     */
    public function isOpenForRegistration(): bool
    {
        return $this->isPublished() && now()->isBefore($this->registrationDeadline());
    }

    /**
     * Full, but visitors can still join the waitlist.
     */
    public function acceptsWaitlist(): bool
    {
        return $this->waitlist_enabled && $this->isOpenForRegistration() && $this->isSoldOut();
    }

    /**
     * Single-model counterpart of scopeWithSeatCounts().
     */
    public function loadSeatCount(): static
    {
        return $this->loadCount(['registrations as active_registrations_count' => fn ($q) => $q->active()]);
    }

    /**
     * Adds active_registrations_count (seats taken), used by seatsTaken() without a query per event.
     *
     * @param  Builder<self>  $query
     */
    public function scopeWithSeatCounts(Builder $query): void
    {
        $query->withCount(['registrations as active_registrations_count' => fn ($q) => $q->active()]);
    }

    /**
     * Levels 3 and 4 ask hikers to confirm they are ready for the level.
     */
    public function requiresLevelAcknowledgement(): bool
    {
        return $this->difficulty_level->value >= DifficultyLevel::Advanced->value;
    }

    public function registerLabel(): string
    {
        return $this->requiresLevelAcknowledgement() ? "Register for Level {$this->difficulty_level->value}" : 'Reserve Your Spot';
    }

    /**
     * "6 h", "4.5 h"; null when the organizer left the duration empty.
     */
    public function formattedDuration(): ?string
    {
        return $this->duration_hours ? rtrim(rtrim(number_format($this->duration_hours, 1), '0'), '.').' h' : null;
    }

    /**
     * Itinerary lines split into an optional leading time ("07:30", "7.30", "7h30") and the step.
     *
     * @return list<array{time: ?string, text: string}>
     */
    public function itinerarySteps(): array
    {
        return array_map(function (string $line) {
            if (preg_match('/^(\d{1,2})[:.h](\d{2})\s*[-–—:]?\s*(.+)$/u', $line, $m) && (int) $m[1] < 24 && (int) $m[2] < 60) {
                return ['time' => sprintf('%02d:%s', $m[1], $m[2]), 'text' => $m[3]];
            }

            return ['time' => null, 'text' => $line];
        }, $this->itinerary ?? []);
    }

    public function coverImageUrl(): ?string
    {
        return $this->cover_image_path ? Storage::disk('public')->url($this->cover_image_path) : null;
    }

    public function gpxUrl(): ?string
    {
        return $this->gpx_path ? Storage::disk('public')->url($this->gpx_path) : null;
    }
}
