<?php

namespace App\Domain\Registrations\Data;

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
    ) {}
}
