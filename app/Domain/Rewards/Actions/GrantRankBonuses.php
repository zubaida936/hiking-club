<?php

namespace App\Domain\Rewards\Actions;

use App\Domain\Loyalty\Enums\PointType;
use App\Domain\Rewards\HikerRewards;
use App\Domain\Rewards\Reward;
use App\Models\Hiker;
use Illuminate\Support\Facades\DB;

/**
 * Adds the one-time bonus points of the ranks a hiker has reached (e.g. Mountaineer +5).
 * Runs after every points change; a bonus is only ever granted once per hiker.
 */
final class GrantRankBonuses
{
    public function __construct(private readonly HikerRewards $rewards) {}

    public function handle(Hiker $hiker): void
    {
        DB::transaction(function () use ($hiker) {
            Hiker::whereKey($hiker->id)->lockForUpdate()->first();

            foreach ($this->rewards->statesFor($hiker) as $state) {
                $reward = $state['reward'];
                if ($reward->kind !== Reward::BONUS_POINTS || ! $state['unlocked'] || $state['uses_left'] === 0) {
                    continue;
                }

                $hiker->rewardRedemptions()->create(['reward' => $reward->key]);
                $hiker->pointTransactions()->create([
                    'type' => PointType::RankBonus,
                    'points' => $reward->points,
                    'description' => "{$reward->tier->label()} rank bonus",
                ]);
            }
        });
    }
}
