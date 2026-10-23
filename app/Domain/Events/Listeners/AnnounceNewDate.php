<?php

namespace App\Domain\Events\Listeners;

use App\Domain\Events\Actions\PostEventUpdate;
use App\Domain\Events\Events\EventRescheduled;
use App\Domain\Registrations\Enums\RegistrationStatus;

/**
 * Tells everyone booked that the hike moved (as an organizer update) and lets
 * the reminder go out again before the new date.
 */
final class AnnounceNewDate
{
    public function __construct(private readonly PostEventUpdate $post) {}

    public function handle(EventRescheduled $rescheduled): void
    {
        $event = $rescheduled->event;

        if (! $event->registrations()->where('status', '!=', RegistrationStatus::Cancelled)->exists()) {
            return;
        }

        $event->registrations()->update(['reminder_sent_at' => null]);

        $this->post->handle(
            $event,
            "New date: this hike moves from {$rescheduled->previousDate->format('l, j F Y')} to {$event->starts_on->format('l, j F Y')}. "
                ."Your booking stays valid. If you can't make the new date, cancel from My account or message us on WhatsApp.",
            $rescheduled->actor,
        );
    }
}
