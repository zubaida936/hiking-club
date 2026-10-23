<?php

namespace App\Domain\Registrations\Actions;

use App\Domain\Registrations\Data\RegistrationData;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Domain\Registrations\Exceptions\RegistrationException;
use App\Mail\RegistrationConfirmationMail;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Books a seat on an event. The event row is locked while seats are counted,
 * so two people can never take the last seat. X events need an organizer's
 * approval: their bookings hold a seat as "pending". When the event is full
 * and has a waitlist, the visitor joins the waitlist instead.
 */
final class RegisterForEvent
{
    /**
     * No 0/O or 1/I, so references read back clearly over the phone.
     */
    private const REFERENCE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function handle(Event $event, RegistrationData $data): EventRegistration
    {
        $registration = DB::transaction(function () use ($event, $data) {
            /** @var Event $event */
            $event = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();

            if (! $event->isOpenForRegistration()) {
                throw RegistrationException::closed();
            }
            if ($event->requiresLevelAcknowledgement() && ! $data->levelAcknowledged) {
                throw RegistrationException::levelNotAcknowledged();
            }
            if ($event->registrations()->where('status', '!=', RegistrationStatus::Cancelled)->where('phone', $data->phone)->exists()) {
                throw RegistrationException::alreadyRegistered();
            }

            $status = match (true) {
                ! $event->isSoldOut() => $event->is_xtreme ? RegistrationStatus::Pending : RegistrationStatus::Confirmed,
                $event->waitlist_enabled => RegistrationStatus::Waitlisted,
                default => throw RegistrationException::soldOut(),
            };

            $pickup = $data->meetingPointId !== null ? $event->meetingPoints()->find($data->meetingPointId) : null;

            return $event->registrations()->create([
                'reference' => $this->uniqueReference(),
                'token' => Str::random(64),
                'status' => $status,
                'name' => $data->name,
                'phone' => $data->phone,
                'email' => $data->email,
                'pickup_point' => $pickup?->name,
                'pickup_time' => $pickup?->pickup_time,
                'emergency_contact_name' => $data->emergencyContactName,
                'emergency_contact_phone' => $data->emergencyContactPhone,
                'notes' => $data->notes,
                'terms_accepted_at' => now(),
                'level_acknowledged_at' => $event->requiresLevelAcknowledgement() ? now() : null,
            ]);
        });

        if ($registration->email !== null) {
            Mail::to($registration->email)->queue(new RegistrationConfirmationMail($registration));
        }

        return $registration;
    }

    private function uniqueReference(): string
    {
        do {
            $reference = '';
            for ($i = 0; $i < 8; $i++) {
                $reference .= self::REFERENCE_ALPHABET[random_int(0, strlen(self::REFERENCE_ALPHABET) - 1)];
            }
        } while (EventRegistration::where('reference', $reference)->exists());

        return $reference;
    }
}
