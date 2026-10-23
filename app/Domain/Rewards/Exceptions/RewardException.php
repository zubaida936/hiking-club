<?php

namespace App\Domain\Rewards\Exceptions;

use DomainException;

/**
 * Reward rule violations. Messages are safe to show to members and staff.
 */
final class RewardException extends DomainException
{
    public static function notAvailable(): self
    {
        return new self('This reward is not available to you (not unlocked yet, or already used).');
    }

    public static function notForThisHike(): self
    {
        return new self("This reward can't be used on this hike.");
    }

    public static function onlyOwnBooking(): self
    {
        return new self('Rewards can only be used on a booking with your own phone number.');
    }

    public static function notHandedOver(): self
    {
        return new self('Only gift-type rewards are handed over by organizers.');
    }
}
