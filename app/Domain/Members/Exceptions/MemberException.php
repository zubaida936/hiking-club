<?php

namespace App\Domain\Members\Exceptions;

use DomainException;

/**
 * Account rule violations. Messages are safe to show to visitors and never
 * reveal whether a phone number has bookings or an account.
 */
final class MemberException extends DomainException
{
    public static function cannotActivate(): self
    {
        return new self("We couldn't match this phone number and booking reference. Check both on your booking confirmation.");
    }

    public static function alreadyActive(): self
    {
        return new self('This phone number already has an account. Log in, or reset your password if you forgot it.');
    }

    public static function invalidCode(): self
    {
        return new self('This code is wrong or has expired. Ask for a new one.');
    }
}
