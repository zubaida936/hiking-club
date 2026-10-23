<?php

namespace App\Domain\Events\Actions;

use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Mail\EventUpdateMail;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventUpdate;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Organizers tell everyone booked on a hike (and on its waitlist) about a change:
 * weather, route, timing. Emailed to each booking with an email address, and shown
 * on the event page and every booking page.
 */
final class PostEventUpdate
{
    public function handle(Event $event, string $message, User $actor): EventUpdate
    {
        $update = $event->updates()->create(['message' => $message, 'created_by' => $actor->id]);

        $emailed = 0;
        EventRegistration::query()
            ->whereBelongsTo($event)
            ->where('status', '!=', RegistrationStatus::Cancelled)
            ->whereNotNull('email')
            ->lazyById()
            ->each(function (EventRegistration $registration) use ($update, &$emailed) {
                Mail::to((string) $registration->email)->queue(new EventUpdateMail($registration, $update));
                $emailed++;
            });

        $update->update(['emailed_count' => $emailed]);

        return $update;
    }
}
