<?php

namespace App\Domain\Loyalty\Listeners;

use App\Domain\Loyalty\Actions\SyncRegistrationPoints;
use App\Domain\Registrations\Events\AttendanceMarked;
use App\Domain\Registrations\Events\RegistrationChanged;
use App\Domain\Rewards\Actions\GrantRankBonuses;
use App\Models\Hiker;

/**
 * Keeps points in step with bookings, then grants any rank bonus the new total
 * unlocked. Runs right away (not queued) so the CMS shows the new totals immediately.
 */
final class SyncPointsForRegistration
{
    public function __construct(
        private readonly SyncRegistrationPoints $sync,
        private readonly GrantRankBonuses $bonuses,
    ) {}

    public function handle(AttendanceMarked|RegistrationChanged $changed): void
    {
        $registration = $changed->registration;

        $this->sync->handle($registration);

        // The hiker and (for referral bonuses) the friend who invited them.
        foreach (Hiker::whereKey(array_filter([$registration->hiker_id, $registration->referred_by_hiker_id]))->get() as $hiker) {
            $this->bonuses->handle($hiker);
        }
    }
}
