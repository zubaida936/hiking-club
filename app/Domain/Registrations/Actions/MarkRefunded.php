<?php

namespace App\Domain\Registrations\Actions;

use App\Domain\Registrations\Exceptions\RegistrationException;
use App\Models\EventRegistration;
use App\Models\User;

/**
 * An organizer paid the money of a cancelled, paid booking back to the hiker
 * (Whish, OMT, cash or the online provider's dashboard).
 */
final class MarkRefunded
{
    public function handle(EventRegistration $registration, User $actor): void
    {
        if (! $registration->refundDue()) {
            throw RegistrationException::notRefundable();
        }

        $registration->update(['refunded_at' => now(), 'updated_by' => $actor->id]);
    }
}
