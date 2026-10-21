<?php

namespace App\Domain\Events\Events;

use App\Models\Event;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Raised when an organizer cancels an event. Registration and notification
 * modules subscribe to this to inform registered hikers.
 */
final class EventCancelled
{
    use Dispatchable;

    public function __construct(public readonly Event $event) {}
}
