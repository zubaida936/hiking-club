<?php

namespace App\Domain\Newsletter\Listeners;

use App\Domain\Events\Events\EventPublished;
use App\Mail\NewEventAnnouncementMail;
use App\Models\Subscriber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Emails confirmed subscribers when an upcoming event is published for the
 * first time. Runs on the queue and queues one mail per subscriber.
 */
final class AnnounceNewEvent implements ShouldQueue
{
    public function handle(EventPublished $published): void
    {
        $event = $published->event->fresh();

        // Re-check on the queue: the event may have been unpublished or cancelled meanwhile.
        if (! $published->firstTime || $event === null || ! $event->isPublished() || ! $event->isUpcoming()) {
            return;
        }

        Subscriber::receivingEmails()->chunkById(200, function ($subscribers) use ($event) {
            foreach ($subscribers as $subscriber) {
                Mail::to($subscriber->email)->queue(new NewEventAnnouncementMail($event, $subscriber));
            }
        });
    }
}
