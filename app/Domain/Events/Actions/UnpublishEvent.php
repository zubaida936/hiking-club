<?php

namespace App\Domain\Events\Actions;

use App\Domain\Events\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;

final class UnpublishEvent
{
    public function handle(Event $event, User $actor): Event
    {
        if ($event->isPublished()) {
            $event->update(['status' => EventStatus::Draft, 'updated_by' => $actor->id]);
        }

        return $event;
    }
}
