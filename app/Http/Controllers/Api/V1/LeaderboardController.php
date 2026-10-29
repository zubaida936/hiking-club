<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Loyalty\Leaderboard;
use App\Http\Controllers\Controller;
use App\Models\Hiker;
use App\Models\User;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    /**
     * Hikers listed at most, same as the website page.
     */
    public const SIZE = 100;

    /**
     * Leaderboard.
     *
     * Hikers by points (ties by km hiked), as on the website: names shown as "Rima H.", only hikers with
     * points who haven't hidden themselves. With a token, `my_position` is the member's place (null when
     * hidden or without points) and their row has `is_me`.
     *
     * @unauthenticated
     *
     * @return array{data: list<array<string, mixed>>, my_position: ?int}
     */
    public function __invoke(Request $request, Leaderboard $leaderboard): array
    {
        $request->validate(['limit' => ['sometimes', 'integer', 'min:1', 'max:'.self::SIZE]]);

        /** @var ?User $user */
        $user = $request->user();
        $me = $user?->hiker;

        return [
            'data' => $leaderboard->top($request->integer('limit', self::SIZE))->values()->map(fn (Hiker $hiker, int $index) => [
                'position' => $index + 1,
                'name' => $hiker->publicName(),
                'rank' => ['key' => $hiker->tier()->value, 'label' => $hiker->tier()->label()],
                'points' => $hiker->totalPoints(),
                'hikes_completed' => (int) $hiker->getAttribute('hikes_completed'),
                'distance_km' => round($hiker->totalDistanceKm(), 1),
                'is_me' => $me !== null && $me->id === $hiker->id,
            ])->all(),
            'my_position' => $me ? $leaderboard->publicPositionOf($me) : null,
        ];
    }
}
