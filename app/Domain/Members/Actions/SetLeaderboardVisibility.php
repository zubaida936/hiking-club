<?php

namespace App\Domain\Members\Actions;

use App\Models\Hiker;

/**
 * A member shows or hides themselves on the public leaderboard.
 */
final class SetLeaderboardVisibility
{
    public function handle(Hiker $hiker, bool $visible): void
    {
        $hiker->update(['show_on_leaderboard' => $visible]);
    }
}
