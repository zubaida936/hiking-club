<?php

namespace App\Domain\Registrations\Actions;

use App\Domain\Registrations\Exceptions\RegistrationException;
use App\Models\EventRegistration;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * A member cancels their own booking (or waitlist entry) from their dashboard.
 * Unpaid: while bookings for the hike are open. Paid: until the paid-cancellation
 * deadline (config site.booking.paid_cancellation_days), then the club owes a refund.
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
        if ($registration->paid_at !== null && ! now()->isBefore($registration->event->paidCancellationDeadline())) {
            throw RegistrationException::paidCancellationClosed(self::lastMoment($registration));
        }
        if ($registration->paid_at === null && ! $registration->event->isOpenForRegistration()) {
            throw RegistrationException::cancellationClosed();
        }

        $this->cancel->handle($registration, 'Cancelled by the hiker', $member);
    }

    /**
     * Whether the dashboard should offer the Cancel button.
     */
    public static function allowed(EventRegistration $registration): bool
    {
        if ($registration->isCancelled()) {
            return false;
        }

        return now()->isBefore(self::deadline($registration));
    }

    /**
     * Last moment to cancel online, for display ("Wed 4 Nov, 23:59", Lebanon time).
     */
    public static function lastMoment(EventRegistration $registration): string
    {
        return self::deadline($registration)->subMinute()->setTimezone(config('site.timezone'))->format('D j M, H:i');
    }

    /**
     * Until when the member can cancel online (UTC).
     */
    public static function deadline(EventRegistration $registration): CarbonImmutable
    {
        $event = $registration->event;

        if ($registration->paid_at !== null) {
            return $event->paidCancellationDeadline();
        }

        // Unpaid: as long as bookings are open (published and before the booking deadline).
        return $event->isPublished() ? $event->registrationDeadline() : CarbonImmutable::now()->subSecond();
    }
}
