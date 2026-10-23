<?php

namespace App\Domain\Registrations\Data;

use App\Domain\Payments\Enums\PaymentMethod;

final readonly class RegistrationData
{
    /**
     * Phones are normalized (App\Support\Phone); $meetingPointId is one of the event's meeting points.
     */
    public function __construct(
        public string $name,
        public string $phone,
        public ?string $email,
        public ?int $meetingPointId,
        public string $emergencyContactName,
        public string $emergencyContactPhone,
        public ?string $notes,
        public bool $levelAcknowledged,
        public ?string $referrerPhone = null,
        public ?PaymentMethod $paymentMethod = null,
        // config/rewards.php key of a discount the logged-in member uses on this booking.
        public ?string $rewardKey = null,
        // Hiker of the logged-in member making the booking (rewards and rank perks only apply to their own bookings).
        public ?int $memberHikerId = null,
    ) {}
}
