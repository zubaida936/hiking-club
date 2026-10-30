<?php

namespace App\Domain\Loyalty;

use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Loyalty\Enums\RankTier;

/**
 * The texts and the worked example of the Rank System page, built from config/points.php. The website page and
 * the app's screen (GET /api/v1/rank-system) both read them here, so they never differ.
 */
final class RankSystemGuide
{
    /**
     * The terms ("the fine print"), one rule per line.
     *
     * @return list<string>
     */
    public function terms(): array
    {
        $unpaid = config('points.paid_only') ? ' or unpaid' : '';

        return [
            "Points and rewards belong to one hiker, identified by the phone number used to book. They can't be transferred or exchanged for cash.",
            "A reward is used on a booking you make yourself, logged in, with your own phone number. One reward per booking; rewards can't be combined.",
            'Rewards are one-time unless their description says otherwise. Cancelling a booking gives its reward back.',
            "Points come from completed hikes only. If a hike is marked as not attended, cancelled{$unpaid} afterwards, its points are removed.",
            'If your points drop below a rank, the unused rewards of that rank are locked again until you climb back.',
            "Points don't expire. The club may correct points that were added by mistake and may change the rank system; changes are announced on this page.",
        ];
    }

    /**
     * A Level 2 hike booked early, and about how many of those reach the second rank.
     *
     * @return array{level: DifficultyLevel, hike_points: int, early_booking_points: int, total: int, rank: RankTier, hikes_to_rank: int}
     */
    public function example(): array
    {
        $level = DifficultyLevel::Endurance;
        $hikePoints = (int) config("points.hike.{$level->value}");
        $earlyPoints = (int) config('points.early_booking.points');
        $total = $hikePoints + $earlyPoints;
        $rank = RankTier::cases()[1];

        return [
            'level' => $level,
            'hike_points' => $hikePoints,
            'early_booking_points' => $earlyPoints,
            'total' => $total,
            'rank' => $rank,
            'hikes_to_rank' => (int) ceil($rank->minPoints() / max(1, $total)),
        ];
    }
}
