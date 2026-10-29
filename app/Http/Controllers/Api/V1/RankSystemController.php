<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Loyalty\Enums\RankTier;
use App\Domain\Rewards\Reward;
use App\Domain\Rewards\RewardCatalog;
use App\Http\Controllers\Controller;

/**
 * How the rank system works. Every number comes from config/points.php and config/rewards.php,
 * like the website's Rank System page, so the app always matches the points engine.
 */
class RankSystemController extends Controller
{
    /**
     * Rank system.
     *
     * The ranks with the points they need and their rewards, and how points are earned. The member's own
     * points and rank are in `GET /me`.
     *
     * @unauthenticated
     *
     * @return array{data: array<string, mixed>}
     */
    public function __invoke(RewardCatalog $catalog): array
    {
        return ['data' => [
            'ranks' => array_map(fn (RankTier $tier) => [
                'key' => $tier->value,
                'label' => $tier->label(),
                'min_points' => $tier->minPoints(),
                'rewards' => array_map(fn (Reward $reward) => [
                    'key' => $reward->key,
                    'label' => $reward->label,
                    'kind' => $reward->kind,
                    'how_it_works' => $reward->howItWorks(),
                ], $catalog->forTier($tier)),
            ], RankTier::cases()),
            'points' => [
                'per_hike' => array_map(fn (DifficultyLevel $level) => [
                    'level' => $level->value,
                    'label' => $level->label(),
                    'points' => (int) config("points.hike.{$level->value}"),
                ], DifficultyLevel::cases()),
                // Bonus for booking at least `days` days before the hike.
                'early_booking' => ['points' => (int) config('points.early_booking.points'), 'days' => (int) config('points.early_booking.days')],
                // For the member who brought a new hiker, once that hiker completes their first hike.
                'referral' => (int) config('points.referral'),
                // Only paid hikes count (free hikes always count).
                'paid_only' => (bool) config('points.paid_only'),
            ],
        ]];
    }
}
