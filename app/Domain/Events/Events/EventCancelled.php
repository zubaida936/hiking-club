<?php

namespace App\Domain\Events\Events;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Raised when an organizer cancels an event. The Registrations module cancels
 * its bookings and tells the hikers (CancelBookingsOfCancelledEvent).
 */
final class EventCancelled
{
    use Dispatchable;

    public function __construct(
        public readonly Event $event,
        public readonly User $actor,
    ) {}
}
