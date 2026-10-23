<?php

namespace App\Domain\XEvents\Exceptions;

use DomainException;

/**
 * X Events rule violations. Messages are safe to show to hikers and staff.
 */
final class XApplicationException extends DomainException
{
    public static function alreadyApplied(): self
    {
        return new self('You already have an application under review. We will contact you on WhatsApp.');
    }

    public static function alreadyApproved(): self
    {
        return new self("You're already approved for X Events. Log in to book them.");
    }

    public static function notPending(): self
    {
        return new self('Only applications under review can be approved or rejected.');
    }

    public static function notApproved(): self
    {
        return new self('Only approved hikers have X Events access to revoke or send.');
    }
}
