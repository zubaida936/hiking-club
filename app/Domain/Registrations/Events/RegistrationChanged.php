<?php

namespace App\Domain\Registrations\Events;

use App\Models\EventRegistration;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An organizer changed a booking (status, payment, hiker details). Attendance
 * changes raise AttendanceMarked instead. The points engine re-checks the booking.
 */
final class RegistrationChanged
{
    use Dispatchable;

    public function __construct(public readonly EventRegistration $registration) {}
}
