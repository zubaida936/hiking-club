<?php

namespace App\Domain\XEvents;

use App\Domain\Rewards\HikerRewards;
use App\Domain\Rewards\Reward;
use App\Models\Hiker;

/**
 * Who may book X Events: hikers approved after applying, and Elite members
 * (their rank reward). Approval lasts across X events until an organizer revokes it.
 */
final class XAccess
{
    public function __construct(private readonly HikerRewards $rewards) {}

    public function allows(?Hiker $hiker): bool
    {
        if ($hiker === null) {
            return false;
        }

        return $hiker->x_approved_at !== null || $this->rewards->has($hiker, Reward::XTREME);
    }
}
