<?php

namespace App\Domain\Newsletter\Data;

use App\Support\Phone;

final readonly class SubscriptionData
{
    public function __construct(
        public ?string $email,
        public ?string $phone,
    ) {}

    public static function from(?string $email, ?string $phone): self
    {
        $email = $email !== null ? strtolower(trim($email)) : '';

        return new self($email !== '' ? $email : null, Phone::normalize($phone));
    }
}
