<?php

namespace App\Domain\Identity\Exceptions;

use DomainException;

/**
 * Business-rule violations. Messages are safe to show to CMS users.
 */
final class StaffUserException extends DomainException
{
    public static function lastSuperAdmin(): self
    {
        return new self('At least one active super admin must remain.');
    }

    public static function cannotDeleteSelf(): self
    {
        return new self('You cannot delete your own account.');
    }

    public static function cannotDeactivateSelf(): self
    {
        return new self('You cannot deactivate your own account.');
    }
}
