<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Hiker;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The logged-in member. "hiker" is null for an account without a hiker profile (e.g. staff who never booked).
 * Load it with ->load('hiker') and the hiker's total_points with loadSum() before returning it.
 *
 * @mixin User
 */
class MemberResource extends JsonResource
{
    /**
     * Loads what the resource needs (hiker profile and points) and wraps the user.
     */
    public static function forUser(User $user): self
    {
        $user->load('hiker');
        $user->hiker?->loadSum('pointTransactions as total_points', 'points');

        return new self($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ?Hiker $hiker */
        $hiker = $this->hiker;
        $next = $hiker?->tier()->next();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'hiker' => $hiker === null ? null : [
                'points' => $hiker->totalPoints(),
                'rank' => ['key' => $hiker->tier()->value, 'label' => $hiker->tier()->label()],
                'next_rank' => $next === null ? null : [
                    'key' => $next->value,
                    'label' => $next->label(),
                    'points_needed' => $hiker->pointsToNextTier(),
                ],
                'rank_progress_percent' => $hiker->tierProgressPercent(),
                'show_on_leaderboard' => $hiker->show_on_leaderboard,
                'x_events_approved' => $hiker->x_approved_at !== null,
            ],
        ];
    }
}
