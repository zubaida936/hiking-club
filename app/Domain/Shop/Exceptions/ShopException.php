<?php

namespace App\Domain\Shop\Exceptions;

use DomainException;

/**
 * Shop rule violations. Messages are safe to show to customers and staff.
 */
final class ShopException extends DomainException
{
    public static function cartEmpty(): self
    {
        return new self('Your cart is empty.');
    }

    public static function notEnoughStock(string $item, int $left): self
    {
        return new self($left === 0
            ? "Sorry, {$item} just sold out. Remove it from your cart to continue."
            : "Sorry, only {$left} left of {$item}. Lower the quantity in your cart to continue.");
    }

    public static function unavailable(): self
    {
        return new self('This item is not available.');
    }

    public static function notAllowed(): self
    {
        return new self('This order can no longer be changed that way.');
    }

    public static function alreadyPaid(): self
    {
        return new self('This order is already paid. Thank you!');
    }
}
