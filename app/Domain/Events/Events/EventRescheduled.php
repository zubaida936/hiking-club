<?php

namespace App\Domain\Events\Events;

use App\Models\Event;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An organizer moved a published event to another date.
 */
final class EventRescheduled
{
    use Dispatchable;

    public function __construct(
        public readonly Event $event,
        public readonly CarbonImmutable $previousDate,
        public readonly User $actor,
    ) {}
}
