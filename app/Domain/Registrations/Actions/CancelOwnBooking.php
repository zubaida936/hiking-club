<?php

namespace App\Domain\Registrations\Actions;

use App\Domain\Registrations\Exceptions\RegistrationException;
use App\Models\EventRegistration;
use App\Models\User;

/**
 * A member cancels their own booking (or waitlist entry) from their dashboard.
 * Only unpaid bookings, and only while bookings for the hike are still open:
 * paid ones need an organizer to arrange the refund.
 */
final class CancelOwnBooking
{
    public function __construct(private readonly CancelRegistration $cancel) {}

    /**
     * @throws RegistrationException
     */
    public function handle(EventRegistration $registration, User $member): void
    {
        if ($registration->isCancelled()) {
            throw RegistrationException::alreadyCancelled();
        }
        if ($registration->paid_at !== null) {
            throw RegistrationException::paidCancelNeedsOrganizer();
        }
        if (! $registration->event->isOpenForRegistration()) {
            throw RegistrationException::cancellationClosed();
        }

        $this->cancel->handle($registration, 'Cancelled by the hiker', $member);
    }

    /**
     * Whether the dashboard should offer the Cancel button.
     */
    public static function allowed(EventRegistration $registration): bool
    {
        return ! $registration->isCancelled()
            && $registration->paid_at === null
            && $registration->event->isOpenForRegistration();
    }
}
