<?php

namespace App\Domain\Registrations\Actions;

use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Domain\Registrations\Exceptions\RegistrationException;
use App\Mail\RegistrationConfirmationMail;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Confirms a booking that awaited approval (X events) and tells the hiker.
 */
final class ApproveRegistration
{
    public function handle(EventRegistration $registration, User $actor): void
    {
        if (! $registration->isPending()) {
            throw RegistrationException::notPending();
        }

        $registration->update(['status' => RegistrationStatus::Confirmed, 'updated_by' => $actor->id]);

        if ($registration->email !== null) {
            Mail::to($registration->email)->queue(new RegistrationConfirmationMail($registration));
        }
    }
}
