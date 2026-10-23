<?php

namespace App\Domain\Loyalty\Actions;

use App\Domain\Loyalty\Enums\PointType;
use App\Models\Hiker;
use App\Models\PointTransaction;
use App\Models\User;

/**
 * A manual correction by an organizer (positive or negative), with the reason.
 */
final class AdjustPoints
{
    public function handle(Hiker $hiker, int $points, string $reason, User $actor): PointTransaction
    {
        return $hiker->pointTransactions()->create([
            'type' => PointType::Adjustment,
            'points' => $points,
            'description' => $reason,
            'created_by' => $actor->id,
        ]);
    }
}
