<?php

namespace App\Domain\Registrations\Actions;

use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Domain\Registrations\Exceptions\RegistrationException;
use App\Mail\RegistrationCancelledMail;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Cancels a booking or waitlist entry for the hiker (they asked, or the organizer
 * decided). The freed seat goes to the waitlist; the hiker is emailed if $notify.
 */
final class CancelRegistration
{
    public function __construct(private readonly FillFreedSeats $fillFreedSeats) {}

    public function handle(EventRegistration $registration, ?string $reason, User $actor, bool $notify = true): void
    {
        if ($registration->isCancelled()) {
            throw RegistrationException::alreadyCancelled();
        }

        $heldSeat = $registration->holdsSeat();

        $registration->update([
            'status' => RegistrationStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'updated_by' => $actor->id,
        ]);

        if ($notify && $registration->email !== null) {
            Mail::to($registration->email)->queue(new RegistrationCancelledMail($registration));
        }

        if ($heldSeat) {
            $this->fillFreedSeats->handle($registration->event);
        }
    }
}
