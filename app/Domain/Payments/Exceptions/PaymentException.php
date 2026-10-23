<?php

namespace App\Domain\Payments\Exceptions;

use DomainException;

/**
 * Payment rule violations. Messages are safe to show to hikers.
 */
final class PaymentException extends DomainException
{
    public static function notPayable(): self
    {
        return new self('This booking can be paid once your seat is confirmed.');
    }

    public static function alreadyPaid(): self
    {
        return new self('This booking is already paid. Thank you!');
    }

    public static function onlineUnavailable(): self
    {
        return new self('Online payment is not available right now. Please choose another way to pay.');
    }

    public static function notAWalletTransfer(): self
    {
        return new self('A transaction number is only needed for Whish Money or OMT Pay transfers.');
    }
}
