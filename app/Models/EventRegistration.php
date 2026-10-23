<?php

namespace App\Models;

use App\Domain\Registrations\Enums\RegistrationStatus;
use Database\Factories\EventRegistrationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_id
 * @property string $reference
 * @property string $token
 * @property RegistrationStatus $status
 * @property string $name
 * @property string $phone
 * @property ?string $email
 * @property ?string $pickup_point
 * @property ?string $pickup_time HH:MM:SS, local time
 * @property string $emergency_contact_name
 * @property string $emergency_contact_phone
 * @property ?string $notes
 * @property Carbon $terms_accepted_at
 * @property ?Carbon $level_acknowledged_at
 * @property Carbon $created_at
 */
class EventRegistration extends Model
{
    /** @use HasFactory<EventRegistrationFactory> */
    use HasFactory;

    /**
     * Written only by the Registrations domain actions with explicit attribute arrays.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'terms_accepted_at' => 'datetime',
            'level_acknowledged_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class)->withTrashed();
    }

    /**
     * Registrations that take a seat.
     *
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', RegistrationStatus::active());
    }

    public function isPending(): bool
    {
        return $this->status === RegistrationStatus::Pending;
    }

    public function isWaitlisted(): bool
    {
        return $this->status === RegistrationStatus::Waitlisted;
    }

    /**
     * 1-based place in the event's waitlist (booking order); null when not waitlisted.
     */
    public function waitlistPosition(): ?int
    {
        if (! $this->isWaitlisted()) {
            return null;
        }

        return static::where('event_id', $this->event_id)
            ->where('status', RegistrationStatus::Waitlisted)
            ->where('id', '<=', $this->id)
            ->count();
    }

    public function pickupTimeShort(): ?string
    {
        return $this->pickup_time ? substr($this->pickup_time, 0, 5) : null;
    }
}
