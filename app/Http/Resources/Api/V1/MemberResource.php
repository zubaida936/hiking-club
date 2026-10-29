<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Loyalty\Leaderboard;
use App\Domain\Registrations\Enums\Attendance;
use App\Domain\Registrations\Enums\RegistrationStatus;
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
        $user->hiker?->loadSum('pointTransactions as total_points', 'points')
            ->loadCount(['registrations as hikes_completed' => fn ($q) => $q
                ->where('status', RegistrationStatus::Confirmed)
                ->where('attendance', Attendance::Attended)]);

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
        $position = $hiker === null ? null : app(Leaderboard::class)->publicPositionOf($hiker);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            // The member's own emergency contact (null until they set it in their profile).
            'emergency_contact' => $hiker?->emergency_contact_name === null ? null : [
                'name' => (string) $hiker->emergency_contact_name,
                'phone' => $hiker->emergency_contact_phone === null ? null : (string) $hiker->emergency_contact_phone,
            ],
            'hiker' => $hiker === null ? null : [
                'points' => $hiker->totalPoints(),
                'rank' => ['key' => $hiker->tier()->value, 'label' => $hiker->tier()->label()],
                'next_rank' => $next === null ? null : [
                    'key' => $next->value,
                    'label' => $next->label(),
                    'points_needed' => $hiker->pointsToNextTier(),
                ],
                'rank_progress_percent' => $hiker->tierProgressPercent(),
                // Place on the public leaderboard; null when hidden or without points.
                // (int): the API docs (and the app's types) otherwise guess a string here.
                'leaderboard_position' => $position === null ? null : (int) $position,
                'hikes_completed' => (int) $hiker->getAttribute('hikes_completed'),
                'distance_km' => round($hiker->totalDistanceKm(), 1),
                'show_on_leaderboard' => $hiker->show_on_leaderboard,
                'x_events_approved' => $hiker->x_approved_at !== null,
            ],
        ];
    }
}
