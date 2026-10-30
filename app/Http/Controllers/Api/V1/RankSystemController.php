<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Loyalty\Enums\RankTier;
use App\Domain\Loyalty\RankSystemGuide;
use App\Domain\Rewards\RewardCatalog;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * How the rank system works. Every number comes from config/points.php and config/rewards.php,
 * like the website's Rank System page, so the app always matches the points engine.
 */
class RankSystemController extends Controller
{
    /**
     * Rank system.
     *
     * The ranks with the points they need and their rewards, how points are earned, a worked example and the
     * terms: everything on the website's Rank System page. The member's own points and rank are in `GET /me`.
     *
     * @unauthenticated
     */
    public function __invoke(RewardCatalog $catalog, RankSystemGuide $guide): JsonResponse
    {
        // Loops with casts (not array_map) so the API docs type every field.
        $ranks = [];
        foreach (RankTier::cases() as $tier) {
            $rewards = [];
            foreach ($catalog->forTier($tier) as $reward) {
                $rewards[] = [
                    'key' => (string) $reward->key,
                    'label' => (string) $reward->label,
                    'kind' => (string) $reward->kind,
                    'how_it_works' => (string) $reward->howItWorks(),
                ];
            }
            $ranks[] = [
                'key' => $tier->value,
                'label' => $tier->label(),
                'min_points' => (int) $tier->minPoints(),
                'rewards' => $rewards,
            ];
        }

        $perHike = [];
        foreach (DifficultyLevel::cases() as $level) {
            $perHike[] = [
                'level' => (int) $level->value,
                'label' => $level->label(),
                'points' => (int) config("points.hike.{$level->value}"),
            ];
        }

        $example = $guide->example();
        // Copied into a plain list: returned as it is, the docs would list the exact sentences as the only allowed values.
        $terms = [];
        foreach ($guide->terms() as $term) {
            $terms[] = (string) $term;
        }

        return response()->json(['data' => [
            'ranks' => $ranks,
            'points' => [
                'per_hike' => $perHike,
                // Bonus for booking at least `days` days before the hike.
                'early_booking' => ['points' => (int) config('points.early_booking.points'), 'days' => (int) config('points.early_booking.days')],
                // For the member who brought a new hiker, once that hiker completes their first hike.
                'referral' => (int) config('points.referral'),
                // Only paid hikes count (free hikes always count).
                'paid_only' => (bool) config('points.paid_only'),
            ],
            // A Level 2 hike booked early, and about how many of those reach the second rank.
            'example' => [
                'level_label' => $example['level']->label(),
                'hike_points' => $example['hike_points'],
                'early_booking_points' => $example['early_booking_points'],
                'total' => $example['total'],
                'rank_label' => $example['rank']->label(),
                'hikes_to_rank' => $example['hikes_to_rank'],
            ],
            // The fine print, one rule per line.
            'terms' => $terms,
        ]]);
    }
}
