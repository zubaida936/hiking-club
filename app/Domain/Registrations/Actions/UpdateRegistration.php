<?php

namespace App\Domain\Registrations\Actions;

use App\Domain\Members\Actions\ResolveHiker;
use App\Domain\Registrations\Data\RegistrationDetailsData;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Domain\Registrations\Events\RegistrationChanged;
use App\Domain\Registrations\Exceptions\RegistrationException;
use App\Models\EventRegistration;
use App\Models\User;

/**
 * Corrects a booking's contact details or pickup point. Status, payment and
 * attendance have their own actions.
 */
final class UpdateRegistration
{
    public function __construct(private readonly ResolveHiker $resolveHiker) {}

    public function handle(EventRegistration $registration, RegistrationDetailsData $data, User $actor): void
    {
        if ($registration->isCancelled()) {
            throw RegistrationException::cancelledCannotChange();
        }

        $phoneTaken = EventRegistration::where('event_id', $registration->event_id)
            ->whereKeyNot($registration->id)
            ->where('status', '!=', RegistrationStatus::Cancelled)
            ->where('phone', $data->phone)
            ->exists();

        if ($phoneTaken) {
            throw RegistrationException::phoneTaken();
        }

        $attributes = [
            'name' => $data->name,
            'phone' => $data->phone,
            'email' => $data->email,
            'emergency_contact_name' => $data->emergencyContactName,
            'emergency_contact_phone' => $data->emergencyContactPhone,
            'notes' => $data->notes,
            'updated_by' => $actor->id,
        ];

        // A pickup point is copied from the event's current meeting points; none chosen keeps the old one.
        if ($data->meetingPointId !== null) {
            $point = $registration->event->meetingPoints()->findOrFail($data->meetingPointId);
            $attributes += ['pickup_point' => $point->name, 'pickup_time' => $point->pickup_time];
        }

        // A corrected phone number means the booking (and its points) belongs to that hiker.
        if ($data->phone !== $registration->phone || $registration->hiker_id === null) {
            $attributes['hiker_id'] = $this->resolveHiker->handle($data->phone, $data->name, $data->email)->id;
        }

        $registration->update($attributes);

        RegistrationChanged::dispatch($registration);
    }
}
