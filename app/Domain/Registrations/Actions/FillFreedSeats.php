<?php

namespace App\Domain\Registrations\Actions;

use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Mail\RegistrationConfirmationMail;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Moves waitlisted hikers up, in booking order, while the event has free seats
 * (after the capacity was raised or a booking was cancelled), and emails them.
 * Only for published events that haven't started yet.
 */
final class FillFreedSeats
{
    /**
     * @return list<EventRegistration> the promoted registrations
     */
    public function handle(Event $event): array
    {
        $promoted = DB::transaction(function () use ($event) {
            /** @var Event $event */
            $event = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();

            if (! $event->isPublished() || ! $event->isUpcoming()) {
                return [];
            }

            $waiting = $event->registrations()
                ->where('status', RegistrationStatus::Waitlisted)
                ->orderBy('id')
                ->limit($event->seatsLeft())
                ->get();

            foreach ($waiting as $registration) {
                $registration->update(['status' => RegistrationStatus::Confirmed]);
            }

            return $waiting->all();
        });

        foreach ($promoted as $registration) {
            if ($registration->email !== null) {
                Mail::to($registration->email)->queue(new RegistrationConfirmationMail($registration, promoted: true));
            }
        }

        return array_values($promoted);
    }
}
