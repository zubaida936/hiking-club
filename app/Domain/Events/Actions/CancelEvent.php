<?php

namespace App\Domain\Events\Actions;

use App\Domain\Events\Enums\EventStatus;
use App\Domain\Events\Events\EventCancelled;
use App\Domain\Events\Exceptions\EventException;
use App\Models\Event;
use App\Models\User;

final class CancelEvent
{
    public function handle(Event $event, string $reason, User $actor): Event
    {
        if ($event->status === EventStatus::Cancelled) {
            throw EventException::alreadyCancelled();
        }

        $event->update([
            'status' => EventStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'updated_by' => $actor->id,
        ]);

        EventCancelled::dispatch($event, $actor);

        return $event;
    }
}
