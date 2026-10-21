<?php

namespace App\Domain\Events\Exceptions;

use DomainException;

/**
 * Business-rule violations. Messages are safe to show to CMS users.
 */
final class EventException extends DomainException
{
    public static function needsMeetingPointToPublish(): self
    {
        return new self('Add at least one meeting point before publishing an upcoming event.');
    }

    public static function cannotPublishCancelled(): self
    {
        return new self('A cancelled event cannot be published. Duplicate it to schedule it again.');
    }

    public static function alreadyCancelled(): self
    {
        return new self('This event is already cancelled.');
    }

    public static function cannotDeletePublished(): self
    {
        return new self('Unpublish or cancel the event before deleting it.');
    }
}
