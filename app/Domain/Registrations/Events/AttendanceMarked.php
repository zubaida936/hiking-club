<?php

namespace App\Domain\Registrations\Events;

use App\Domain\Registrations\Enums\Attendance;
use App\Models\EventRegistration;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An organizer marked (or cleared, $attendance null) a hiker's attendance.
 * The points engine listens to this: only attended hikes earn points.
 */
final class AttendanceMarked
{
    use Dispatchable;

    public function __construct(
        public readonly EventRegistration $registration,
        public readonly ?Attendance $attendance,
        public readonly ?Attendance $previous,
    ) {}
}
