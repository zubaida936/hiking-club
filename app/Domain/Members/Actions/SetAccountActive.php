<?php

namespace App\Domain\Members\Actions;

use App\Domain\Members\Exceptions\MemberException;
use App\Models\Hiker;

/**
 * An organizer deactivates (or reactivates) a member's login. Bookings and points stay;
 * a deactivated member can't log in and is logged out on their next request.
 */
final class SetAccountActive
{
    public function handle(Hiker $hiker, bool $active): void
    {
        $user = $hiker->user;

        if ($user === null) {
            throw MemberException::noAccount();
        }
        if ($user->isStaff()) {
            throw MemberException::staffAccount();
        }

        $user->forceFill(['is_active' => $active, 'remember_token' => $active ? $user->remember_token : null])->save();
    }
}
