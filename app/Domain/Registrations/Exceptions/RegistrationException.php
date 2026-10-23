<?php

namespace App\Domain\Registrations\Exceptions;

use DomainException;

/**
 * Booking rule violations. Messages are safe to show to visitors.
 */
final class RegistrationException extends DomainException
{
    public static function closed(): self
    {
        return new self('Registration for this hike is closed.');
    }

    public static function soldOut(): self
    {
        return new self('Sorry, this hike is fully booked.');
    }

    public static function alreadyRegistered(): self
    {
        return new self('This phone number is already registered for this hike. Contact us if you need to change your booking.');
    }

    public static function levelNotAcknowledged(): self
    {
        return new self('Please confirm that you meet the level requirements for this hike.');
    }

    public static function phoneTaken(): self
    {
        return new self('Another booking for this hike already uses this phone number.');
    }

    public static function paidCancelNeedsOrganizer(): self
    {
        return new self('This booking is paid: message us on WhatsApp to cancel it and arrange your refund.');
    }

    public static function cancellationClosed(): self
    {
        return new self('Bookings for this hike are closed, so it can no longer be cancelled online. Message us on WhatsApp.');
    }

    public static function notPending(): self
    {
        return new self('Only bookings awaiting approval can be approved.');
    }

    public static function alreadyCancelled(): self
    {
        return new self('This booking is already cancelled.');
    }

    public static function cancelledCannotChange(): self
    {
        return new self('This booking is cancelled and can no longer be changed.');
    }

    public static function attendanceBeforeHikeDay(): self
    {
        return new self('Attendance can be marked from the day of the hike.');
    }

    public static function attendanceNeedsConfirmedBooking(): self
    {
        return new self('Only confirmed bookings can be marked as attended or no-show.');
    }
}
