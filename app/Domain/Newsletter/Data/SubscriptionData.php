<?php

namespace App\Domain\Newsletter\Data;

final readonly class SubscriptionData
{
    public function __construct(
        public ?string $email,
        public ?string $phone,
    ) {}

    public static function from(?string $email, ?string $phone): self
    {
        $email = $email !== null ? strtolower(trim($email)) : '';
        // Keep a leading "+" and the digits: "+961 70-123 456" -> "+96170123456".
        $phone = $phone !== null ? preg_replace('/(?!^\+)\D/', '', trim($phone)) ?? '' : '';

        return new self($email !== '' ? $email : null, $phone !== '' ? $phone : null);
    }
}
