<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Members\Actions\SetLeaderboardVisibility;
use App\Domain\Registrations\Enums\Attendance;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Domain\Rewards\HikerRewards;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BookingResource;
use App\Http\Resources\Api\V1\MemberResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Http\Resources\Api\V1\PointTransactionResource;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Hiker;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The member dashboard of the website (My account), for the app. An account without a hiker profile
 * (never booked, e.g. staff) has no bookings, points or rewards: the lists are empty.
 */
class AccountController extends Controller
{
    public const PER_PAGE = 20;

    /**
     * My bookings.
     *
     * `upcoming` (default): bookings and waitlist entries for hikes that haven't happened, soonest first
     * (cancelled ones are left out). `completed`: hikes I attended, newest first, with `earned_points`.
     */
    public function bookings(Request $request): AnonymousResourceCollection
    {
        $request->validate(['status' => ['sometimes', 'in:upcoming,completed'], 'page' => ['sometimes', 'integer', 'min:1']]);
        $completed = $request->query('status') === 'completed';
        $hiker = $this->hiker($request);

        $startsOn = Event::withTrashed()->select('starts_on')->whereColumn('events.id', 'event_registrations.event_id');

        $bookings = EventRegistration::query()
            ->where('hiker_id', $hiker->id ?? 0)
            ->when(! $completed, fn (Builder $q) => $q
                ->where('status', '!=', RegistrationStatus::Cancelled)
                ->whereHas('event', fn ($q) => $q->upcoming())
                ->orderBy($startsOn))
            ->when($completed, fn (Builder $q) => $q
                ->where('status', RegistrationStatus::Confirmed)
                ->where('attendance', Attendance::Attended)
                ->withSum(['pointTransactions as earned_points' => fn ($q) => $q->where('hiker_id', $hiker->id ?? 0)], 'points')
                ->orderByDesc($startsOn))
            ->orderBy('id')
            ->with(['event' => fn ($q) => $q->withSeatCounts(), 'rewardRedemption'])
            ->paginate(self::PER_PAGE);

        return BookingResource::collection($bookings);
    }

    /**
     * My shop orders.
     *
     * Orders placed with my phone number, newest first.
     */
    public function orders(Request $request): AnonymousResourceCollection
    {
        $request->validate(['page' => ['sometimes', 'integer', 'min:1']]);

        $orders = Order::query()
            ->where('hiker_id', $this->hiker($request)->id ?? 0)
            ->with('items')
            ->latest('id')
            ->paginate(self::PER_PAGE);

        return OrderResource::collection($orders);
    }

    /**
     * My points history.
     *
     * Every line of the points ledger, newest first. Corrections are new lines (negative points), never edits.
     */
    public function points(Request $request): AnonymousResourceCollection
    {
        $request->validate(['page' => ['sometimes', 'integer', 'min:1']]);

        $lines = PointTransaction::query()
            ->where('hiker_id', $this->hiker($request)->id ?? 0)
            ->with('registration.event')
            ->latest('id')
            ->paginate(self::PER_PAGE);

        return PointTransactionResource::collection($lines);
    }

    /**
     * My rewards.
     *
     * Every reward of the rank system: unlocked or not (and why), uses left, and where I used it.
     * Discounts are chosen on the booking form; perks are handed over by the organizers.
     *
     * @return array{data: list<array<string, mixed>>}
     */
    public function rewards(Request $request, HikerRewards $rewards): array
    {
        $hiker = $this->hiker($request);

        return ['data' => $hiker === null ? [] : array_map(fn (array $state) => [
            'key' => $state['reward']->key,
            'label' => $state['reward']->label,
            'kind' => $state['reward']->kind,
            'rank' => ['key' => $state['reward']->tier->value, 'label' => $state['reward']->tier->label()],
            'how_it_works' => $state['reward']->howItWorks(),
            'unlocked' => $state['unlocked'],
            'locked_reason' => $state['locked_reason'],
            // null for rewards that aren't counted (e.g. X events access).
            'uses_left' => $state['reward']->isCountable() ? $state['uses_left'] : null,
            'used' => $state['redemptions']->map(fn (RewardRedemption $use) => [
                'event' => $use->registration === null ? null : [
                    'slug' => $use->registration->event->slug,
                    'title' => $use->registration->event->title,
                ],
                'discount_cents' => $use->discount_cents,
                'note' => $use->note,
                'used_at' => $use->created_at->toIso8601String(),
            ])->values()->all(),
        ], $rewards->statesFor($hiker))];
    }

    /**
     * Show or hide me on the leaderboard.
     *
     * The public leaderboard shows "Rima H." with points; hidden members are left out.
     */
    public function leaderboard(Request $request, SetLeaderboardVisibility $action): MemberResource
    {
        $request->validate(['show_on_leaderboard' => ['required', 'boolean']]);

        $hiker = $this->hiker($request);
        abort_if($hiker === null, 404, 'You will appear on the leaderboard after your first hike.');

        $action->handle($hiker, $request->boolean('show_on_leaderboard'));

        /** @var User $user */
        $user = $request->user();

        return MemberResource::forUser($user);
    }

    private function hiker(Request $request): ?Hiker
    {
        /** @var User $user */
        $user = $request->user();

        return $user->hiker;
    }
}
