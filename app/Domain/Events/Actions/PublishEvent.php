<?php

namespace App\Domain\Events\Actions;

use App\Domain\Events\Enums\EventStatus;
use App\Domain\Events\Exceptions\EventException;
use App\Models\Event;
use App\Models\User;

final class PublishEvent
{
    public function handle(Event $event, User $actor): Event
    {
        if ($event->status === EventStatus::Cancelled) {
            throw EventException::cannotPublishCancelled();
        }

        // Past events may be published without pickups (archive entries).
        if ($event->isUpcoming() && ! $event->meetingPoints()->exists()) {
            throw EventException::needsMeetingPointToPublish();
        }

        $event->update([
            'status' => EventStatus::Published,
            'published_at' => $event->published_at ?? now(),
            'updated_by' => $actor->id,
        ]);

        return $event;
    }
}
