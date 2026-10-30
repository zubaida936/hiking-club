<?php

namespace App\Http\Resources\Api\V1;

use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One booking on an organizer's check-in list: what the guides need on the trail (who, pickup, payment,
 * emergency contact) and the attendance. Staff only (registrations.view).
 *
 * @mixin EventRegistration
 */
class CheckInResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $paid = $this->paid_at !== null;
        $due = $this->amountDueCents();

        return [
            // Also what the member's QR code holds.
            'reference' => $this->reference,
            'name' => $this->name,
            'phone' => $this->phone,
            // confirmed or pending (awaiting approval); only confirmed bookings can be checked in.
            'status' => ['key' => $this->status->value, 'label' => $this->status->label()],
            'pickup' => $this->pickup_point === null ? null : [
                'point' => (string) $this->pickup_point,
                'time' => $this->pickupTimeShort(),
            ],
            'emergency_contact' => ['name' => $this->emergency_contact_name, 'phone' => $this->emergency_contact_phone],
            'notes' => $this->notes,
            // none (free), paid, or due (with the amount and how they chose to pay).
            'payment' => [
                'state' => $due === 0 ? 'none' : ($paid ? 'paid' : 'due'),
                'amount' => $this->formattedAmountDue(),
                'method' => $this->payment_method?->label(),
            ],
            // attended, no_show, or null when not marked yet.
            'attendance' => $this->attendance?->value,
            'can_check_in' => $this->isConfirmed(),
        ];
    }
}
