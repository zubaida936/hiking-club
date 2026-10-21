<?php

namespace App\Domain\Events\Actions;

use App\Domain\Events\Exceptions\EventException;
use App\Models\Event;

/**
 * Soft delete; the cover image is kept so the event can be restored.
 */
final class DeleteEvent
{
    public function handle(Event $event): void
    {
        if ($event->isPublished()) {
            throw EventException::cannotDeletePublished();
        }

        $event->delete();
    }
}
