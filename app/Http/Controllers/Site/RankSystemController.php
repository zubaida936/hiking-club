<?php

namespace App\Http\Controllers\Site;

use App\Domain\Events\Enums\DifficultyLevel;
use App\Domain\Loyalty\Enums\RankTier;
use App\Domain\Rewards\RewardCatalog;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * How the rank system works: tiers, how points are earned, rewards and terms.
 * Every number comes from config/points.php and config/rewards.php, so the page
 * always matches what the points engine and the booking form do.
 */
class RankSystemController extends Controller
{
    public function __invoke(Request $request, RewardCatalog $catalog): View
    {
        $hiker = $request->user()?->hiker;
        $hiker?->loadSum('pointTransactions as total_points', 'points');

        return view('site.rank-system', [
            'tiers' => array_map(fn (RankTier $tier) => [
                'tier' => $tier,
                'rewards' => $catalog->forTier($tier),
            ], RankTier::cases()),
            'hikePoints' => array_map(fn (DifficultyLevel $level) => [
                'level' => $level,
                'points' => (int) config("points.hike.{$level->value}"),
            ], DifficultyLevel::cases()),
            'earlyBooking' => config('points.early_booking'),
            'referral' => (int) config('points.referral'),
            'paidOnly' => (bool) config('points.paid_only'),
            'hiker' => $hiker,
        ]);
    }
}
