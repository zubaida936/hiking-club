<?php

namespace App\Models;

use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Events\Enums\EventStatus;
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
 * @property ?list<string> $included
 * @property ?list<string> $what_to_bring
 * @property ?string $cover_image_path
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
            'included' => 'array',
            'what_to_bring' => 'array',
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

    public function coverImageUrl(): ?string
    {
        return $this->cover_image_path ? Storage::disk('public')->url($this->cover_image_path) : null;
    }
}
