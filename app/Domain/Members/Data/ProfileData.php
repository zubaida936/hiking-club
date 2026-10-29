<?php

namespace App\Domain\Members\Data;

/**
 * What a member can change about themselves. The phone number is not here: it is the login and links the
 * bookings to the person, and can't be verified without an SMS provider, so the organizers change it.
 */
final readonly class ProfileData
{
    public function __construct(
        public string $name,
        public ?string $email,
        public ?string $emergencyContactName,
        public ?string $emergencyContactPhone,
    ) {}
}
