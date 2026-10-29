<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Loyalty\PointsRules;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Registrations\Actions\CancelOwnBooking;
use App\Models\EventRegistration;
use App\Support\WhatsApp;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A booking, as on the website's booking page (the secret link). Load event (with its seat count)
 * and rewardRedemption first.
 *
 * `token` is the secret that opens the booking (GET /bookings/{token}): the app keeps it, like the
 * hiker keeps the website link. Don't show it to other people.
 *
 * @mixin EventRegistration
 */
class BookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event = $this->event;
        $canCancel = CancelOwnBooking::allowed($this->resource);

        return [
            'reference' => $this->reference,
            'token' => $this->token,
            'status' => ['key' => $this->status->value, 'label' => $this->status->label()],
            'waitlist_position' => $this->waitlistPosition(),
            'event' => new EventResource($event),
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'pickup' => $this->pickup_point === null ? null : ['point' => $this->pickup_point, 'time' => $this->pickupTimeShort()],
            'emergency_contact' => ['name' => $this->emergency_contact_name, 'phone' => $this->emergency_contact_phone],
            'notes' => $this->notes,
            'amount_due' => ['cents' => $this->amountDueCents(), 'formatted' => $this->formattedAmountDue()],
            'reward_used' => $this->rewardRedemption?->reward,
            'payment' => $this->payment(),
            'cancellation' => [
                'cancelled_at' => $this->cancelled_at?->toIso8601String(),
                'reason' => $this->cancellation_reason,
                // Only the logged-in owner can cancel (POST /bookings/{token}/cancel), until `until`.
                'can_cancel' => $canCancel && (bool) $request->user()?->can('cancelOwn', $this->resource),
                'until' => $canCancel ? CancelOwnBooking::deadline($this->resource)->toIso8601String() : null,
            ],
            'points_to_earn' => $this->isCancelled() ? 0 : app(PointsRules::class)->hikePoints($event),
            // Completed hikes in my bookings: the points this booking actually earned.
            'earned_points' => $this->whenHas('earned_points', fn ($points) => (int) $points),
            'booked_at' => $this->created_at->toIso8601String(),
            'web_url' => route('registrations.show', $this->resource),
            'whatsapp_url' => WhatsApp::link("Hi! I booked \"{$event->title}\" on {$event->starts_on->format('D, j M Y')}. My booking reference is {$this->reference}."),
        ];
    }

    /**
     * What the hiker still has to do to pay. `state`:
     *   none (free or cancelled), paid, after_confirmation (pay once the seat is confirmed), due.
     * When due, `instructions.type` is online (POST /bookings/{token}/pay), transfer (send the money,
     * then POST /bookings/{token}/transfer with the transaction number), cash, or contact (the organizers
     * send the details).
     *
     * @return array<string, mixed>
     */
    private function payment(): array
    {
        $method = $this->payment_method;

        $state = match (true) {
            $this->amountDueCents() === 0 || $this->isCancelled() => 'none',
            $this->paid_at !== null => 'paid',
            ! $this->isConfirmed() => 'after_confirmation',
            default => 'due',
        };

        return [
            'state' => $state,
            'method' => $method === null ? null : ['key' => $method->value, 'label' => $method->label()],
            'paid_at' => $this->paid_at?->toIso8601String(),
            'refund_due' => $this->refundDue(),
            'instructions' => $state === 'due' ? $this->instructions($method) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function instructions(?PaymentMethod $method): array
    {
        return match (true) {
            $method === PaymentMethod::Online && PaymentMethod::Online->isAvailable() => ['type' => 'online'],
            (bool) $method?->isWalletTransfer() => [
                'type' => 'transfer',
                'provider' => $method === PaymentMethod::Whish ? 'Whish Money' : 'OMT Pay',
                'account_name' => $method->accountName(),
                'account_number' => $method->accountNumber(),
                // To write in the transfer note.
                'note' => $this->reference,
                // Transaction number the hiker already sent, if any.
                'reported_transaction_number' => $this->payment_reference,
            ],
            $method === PaymentMethod::Cash => ['type' => 'cash', 'message' => "Pay {$this->formattedAmountDue()} in cash to the organizers at the pickup point."],
            default => ['type' => 'contact', 'message' => (string) config('site.booking.payment_note')],
        };
    }
}
