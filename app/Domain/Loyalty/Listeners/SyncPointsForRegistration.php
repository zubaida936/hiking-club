<?php

namespace App\Domain\Loyalty\Listeners;

use App\Domain\Loyalty\Actions\SyncRegistrationPoints;
use App\Domain\Registrations\Events\AttendanceMarked;
use App\Domain\Registrations\Events\RegistrationChanged;

/**
 * Keeps points in step with bookings. Runs right away (not queued) so the CMS
 * shows the new totals immediately.
 */
final class SyncPointsForRegistration
{
    public function __construct(private readonly SyncRegistrationPoints $sync) {}

    public function handle(AttendanceMarked|RegistrationChanged $changed): void
    {
        $this->sync->handle($changed->registration);
    }
}
