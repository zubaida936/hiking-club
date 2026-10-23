<?php

namespace App\Domain\Registrations\Data;

/**
 * Booking details an organizer can correct in the CMS. Phones are normalized.
 */
final readonly class RegistrationDetailsData
{
    public function __construct(
        public string $name,
        public string $phone,
        public ?string $email,
        public ?int $meetingPointId,
        public string $emergencyContactName,
        public string $emergencyContactPhone,
        public ?string $notes,
    ) {}
}
