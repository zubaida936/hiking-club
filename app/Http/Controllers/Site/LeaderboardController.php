<?php

namespace App\Http\Controllers\Site;

use App\Domain\Loyalty\Enums\RankTier;
use App\Domain\Loyalty\Leaderboard;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    /**
     * Hikers listed on the full page.
     */
    public const SIZE = 100;

    public function __invoke(Request $request, Leaderboard $leaderboard): View
    {
        $me = $request->user()?->hiker;

        return view('site.leaderboard', [
            'hikers' => $leaderboard->top(self::SIZE),
            'me' => $me,
            'myPosition' => $me ? $leaderboard->publicPositionOf($me) : null,
            'tiers' => RankTier::cases(),
        ]);
    }
}
