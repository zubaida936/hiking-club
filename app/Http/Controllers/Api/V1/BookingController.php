<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Loyalty\PointsRules;
use App\Domain\Payments\Actions\ReportTransfer;
use App\Domain\Payments\Actions\StartOnlinePayment;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Registrations\Actions\CancelOwnBooking;
use App\Domain\Registrations\Actions\RegisterForEvent;
use App\Domain\Rewards\HikerRewards;
use App\Domain\Rewards\Reward;
use App\Domain\XEvents\XAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\RegistrationRequest;
use App\Http\Resources\Api\V1\BookingResource;
use App\Models\Event;
use App\Models\EventMeetingPoint;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Booking a hike and paying for it, with the website's rules (RegisterForEvent, payments actions).
 * Guests can book like on the website; with a token, the member's details are filled in and they
 * can use rewards and book X events.
 */
class BookingController extends Controller
{
    /**
     * Booking form.
     *
     * Everything the app needs to show the booking form: pickup points, payment methods, the member's
     * rewards for this hike, terms. `open` false: bookings are closed. `waitlist` true: the hike is full
     * and a booking joins the waitlist. `x_locked` true: an X event the hiker can't book (not logged in,
     * or not approved).
     *
     * @unauthenticated
     *
     * @return array{data: array<string, mixed>}
     */
    public function form(Request $request, Event $event, HikerRewards $rewards, XAccess $xAccess, PointsRules $points): array
    {
        abort_unless($event->isVisibleOnSite(), 404);

        $event->load('meetingPoints')->loadSeatCount();
        /** @var ?User $user */
        $user = $request->user();
        $hiker = $user?->hiker;
        $paid = $event->fee_cents > 0;

        return ['data' => [
            'event_slug' => $event->slug,
            'open' => $event->isOpenForRegistration() && (! $event->isSoldOut() || $event->waitlist_enabled),
            'waitlist' => $event->acceptsWaitlist(),
            'x_locked' => $event->is_xtreme && ! $xAccess->allows($hiker),
            'requires_level_acknowledgement' => $event->requiresLevelAcknowledgement(),
            'level_label' => $event->difficulty_level->label(),
            'fee' => ['cents' => $event->fee_cents, 'formatted' => $event->formattedFee()],
            'meeting_points' => $event->meetingPoints->map(fn (EventMeetingPoint $point) => [
                'id' => $point->id,
                'name' => $point->name,
                'pickup_time' => $point->pickupTimeShort(),
            ])->all(),
            // Required for paid hikes (unless a 100% reward is chosen); empty for free hikes.
            // (collect()->map() rather than array_map(): the API docs can type the items then.)
            'payment_methods' => collect($paid ? PaymentMethod::available() : [])->map(fn (PaymentMethod $method) => [
                'key' => $method->value,
                'label' => $method->label(),
            ])->values()->all(),
            // Discounts the member can use on this hike: send its key as `reward`.
            'rewards' => collect($hiker === null ? [] : $rewards->discountsFor($hiker, $event))->map(fn (Reward $reward) => [
                'key' => $reward->key,
                'label' => $reward->label,
                'percent' => (int) $reward->percent,
                'fee_after' => $this->formattedCents($event->fee_cents - $reward->discountCents($event->fee_cents)),
            ])->values()->all(),
            'terms' => (string) config('site.booking.terms'),
            'points_to_earn' => $points->hikePoints($event),
            // The logged-in member's details, to fill in the form.
            'member' => $user === null ? null : [
                'name' => $hiker->name ?? $user->name,
                'phone' => $hiker->phone ?? $user->phone,
                'email' => $hiker->email ?? $user->email,
            ],
        ]];
    }

    /**
     * Book a hike.
     *
     * Same fields and rules as the website form: `terms` must be true, `level_acknowledged` true for
     * level 3+, `meeting_point_id` from the form's `meeting_points`, `payment_method` for paid hikes.
     * A full hike with a waitlist puts the hiker on the waitlist (status `waitlisted`). Rule violations
     * (closed, sold out, already booked, X approval needed, reward not usable) answer 422 with a message.
     *
     * @unauthenticated
     */
    public function store(RegistrationRequest $request, Event $event, RegisterForEvent $action): JsonResponse
    {
        $registration = $action->handle($event, $request->toData());

        return $this->present($registration)->response()->setStatusCode(201);
    }

    /**
     * One booking.
     *
     * Opened with its secret token (from booking, or the booking email link).
     *
     * @unauthenticated
     */
    public function show(EventRegistration $registration): BookingResource
    {
        return $this->present($registration);
    }

    /**
     * Start an online payment.
     *
     * When `payment.instructions.type` is `online`. Open `checkout_url` in the browser; the provider then
     * returns the hiker to the website's booking page. Reload the booking afterwards to see `paid`.
     *
     * @unauthenticated
     *
     * @return array{checkout_url: string}
     */
    public function pay(EventRegistration $registration, StartOnlinePayment $action): array
    {
        return ['checkout_url' => $action->handle($registration)];
    }

    /**
     * Report a Whish / OMT transfer.
     *
     * The transaction number of the transfer, so organizers can match it before marking the booking paid.
     *
     * @unauthenticated
     */
    public function transfer(Request $request, EventRegistration $registration, ReportTransfer $action): BookingResource
    {
        $validated = $request->validate(['payment_reference' => ['required', 'string', 'max:60']]);

        $action->handle($registration, trim($validated['payment_reference']));

        return $this->present($registration);
    }

    /**
     * Cancel my booking.
     *
     * Only the logged-in member who owns the booking (403 otherwise). Unpaid: while bookings are open.
     * Paid: until `cancellation.until`, then the club refunds.
     */
    public function cancel(Request $request, EventRegistration $registration, CancelOwnBooking $action): BookingResource
    {
        Gate::authorize('cancelOwn', $registration);

        /** @var User $user */
        $user = $request->user();
        $action->handle($registration, $user);

        return $this->present($registration->refresh());
    }

    private function present(EventRegistration $registration): BookingResource
    {
        $registration->load('rewardRedemption');
        $registration->event->loadSeatCount();

        return new BookingResource($registration);
    }

    private function formattedCents(int $cents): string
    {
        return $cents === 0 ? 'Free' : '$'.rtrim(rtrim(number_format($cents / 100, 2, '.', ''), '0'), '.');
    }
}
