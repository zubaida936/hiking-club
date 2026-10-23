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
}
