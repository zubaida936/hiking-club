<?php

namespace App\Domain\Loyalty\Actions;

use App\Domain\Loyalty\Enums\PointType;
use App\Domain\Rewards\Actions\GrantRankBonuses;
use App\Models\Hiker;
use App\Models\PointTransaction;
use App\Models\User;

/**
 * A manual correction by an organizer (positive or negative), with the reason.
 */
final class AdjustPoints
{
    public function __construct(private readonly GrantRankBonuses $bonuses) {}

    public function handle(Hiker $hiker, int $points, string $reason, User $actor): PointTransaction
    {
        $transaction = $hiker->pointTransactions()->create([
            'type' => PointType::Adjustment,
            'points' => $points,
            'description' => $reason,
            'created_by' => $actor->id,
        ]);

        $this->bonuses->handle($hiker);

        return $transaction;
    }
}
