<?php

namespace App\Domain\Events\Events;

use App\Models\Event;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Raised when an event is published. $firstTime is false when a previously
 * unpublished event goes live again, so announcements are only sent once.
 */
final class EventPublished
{
    use Dispatchable;

    public function __construct(
        public readonly Event $event,
        public readonly bool $firstTime,
    ) {}
}
