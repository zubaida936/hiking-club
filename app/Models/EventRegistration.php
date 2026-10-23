<?php

namespace App\Models;

use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Registrations\Enums\Attendance;
use App\Domain\Registrations\Enums\RegistrationStatus;
use Database\Factories\EventRegistrationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_id
 * @property ?int $hiker_id
 * @property ?int $referred_by_hiker_id
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
 * @property ?int $amount_due_cents null = the event fee
 * @property ?string $notes
 * @property Carbon $terms_accepted_at
 * @property ?Carbon $level_acknowledged_at
 * @property ?Carbon $paid_at
 * @property ?PaymentMethod $payment_method
 * @property ?string $payment_reference transfer number reported by the hiker, or the gateway's id
 * @property ?Carbon $refunded_at
 * @property ?Attendance $attendance
 * @property ?Carbon $attendance_marked_at
 * @property ?Carbon $cancelled_at
 * @property ?string $cancellation_reason
 * @property ?int $updated_by
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
            'paid_at' => 'datetime',
            'payment_method' => PaymentMethod::class,
            'refunded_at' => 'datetime',
            'attendance' => Attendance::class,
            'attendance_marked_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    /**
     * @return BelongsTo<Hiker, $this>
     */
    public function hiker(): BelongsTo
    {
        return $this->belongsTo(Hiker::class);
    }

    /**
     * @return BelongsTo<Hiker, $this>
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Hiker::class, 'referred_by_hiker_id');
    }

    /**
     * Online payment attempts.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * The reward used on this booking, if any (given back when the booking is cancelled).
     *
     * @return HasOne<RewardRedemption, $this>
     */
    public function rewardRedemption(): HasOne
    {
        return $this->hasOne(RewardRedemption::class)->latestOfMany();
    }

    /**
     * What this booking costs: the event fee, minus a reward discount.
     */
    public function amountDueCents(): int
    {
        return $this->amount_due_cents ?? $this->event->fee_cents;
    }

    public function formattedAmountDue(): string
    {
        $cents = $this->amountDueCents();

        return $cents === 0 ? 'Free' : '$'.rtrim(rtrim(number_format($cents / 100, 2, '.', ''), '0'), '.');
    }

    /**
     * Cancelled after paying, and the club hasn't paid the money back yet.
     */
    public function refundDue(): bool
    {
        return $this->isCancelled() && $this->paid_at !== null && $this->refunded_at === null;
    }

    /**
     * Whether the hiker still has to pay (paid hike, seat confirmed, not paid yet).
     */
    public function awaitsPayment(): bool
    {
        return $this->isConfirmed() && $this->paid_at === null && $this->amountDueCents() > 0;
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
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isConfirmed(): bool
    {
        return $this->status === RegistrationStatus::Confirmed;
    }

    public function isCancelled(): bool
    {
        return $this->status === RegistrationStatus::Cancelled;
    }

    /**
     * Confirmed and awaiting-approval bookings hold a seat.
     */
    public function holdsSeat(): bool
    {
        return in_array($this->status, RegistrationStatus::active(), true);
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
