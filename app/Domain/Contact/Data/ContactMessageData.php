<?php

namespace App\Domain\Contact\Data;

/**
 * A message sent with the contact form.
 */
final readonly class ContactMessageData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public string $message,
    ) {}
}
