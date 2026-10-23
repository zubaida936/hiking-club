<?php

namespace App\Domain\Registrations\Actions;

use App\Domain\Registrations\Events\RegistrationChanged;
use App\Domain\Registrations\Exceptions\RegistrationException;
use App\Models\EventRegistration;
use App\Models\User;

/**
 * Marks a booking paid or unpaid (payment is collected outside the site for now).
 */
final class SetPaymentStatus
{
    public function handle(EventRegistration $registration, bool $paid, User $actor): void
    {
        if ($registration->isCancelled()) {
            throw RegistrationException::cancelledCannotChange();
        }

        $registration->update([
            'paid_at' => $paid ? ($registration->paid_at ?? now()) : null,
            'updated_by' => $actor->id,
        ]);

        RegistrationChanged::dispatch($registration);
    }
}
