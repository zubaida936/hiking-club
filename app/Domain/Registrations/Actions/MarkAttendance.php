<?php

namespace App\Domain\Registrations\Actions;

use App\Domain\Registrations\Enums\Attendance;
use App\Domain\Registrations\Events\AttendanceMarked;
use App\Domain\Registrations\Exceptions\RegistrationException;
use App\Models\EventRegistration;
use App\Models\User;

/**
 * Records whether a confirmed hiker showed up ($attendance null clears it).
 * Allowed from the hike day on; raises AttendanceMarked for the points engine.
 */
final class MarkAttendance
{
    public function handle(EventRegistration $registration, ?Attendance $attendance, User $actor): void
    {
        if (! $registration->isConfirmed()) {
            throw RegistrationException::attendanceNeedsConfirmedBooking();
        }
        if (! $registration->event->hikeDayReached()) {
            throw RegistrationException::attendanceBeforeHikeDay();
        }

        $previous = $registration->attendance;
        if ($previous === $attendance) {
            return;
        }

        $registration->update([
            'attendance' => $attendance,
            'attendance_marked_at' => $attendance ? now() : null,
            'updated_by' => $actor->id,
        ]);

        AttendanceMarked::dispatch($registration, $attendance, $previous);
    }
}
