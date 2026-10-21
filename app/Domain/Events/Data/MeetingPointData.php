<?php

namespace App\Domain\Events\Data;

final readonly class MeetingPointData
{
    public function __construct(
        public string $name,
        public string $pickupTime, // HH:MM local time
    ) {}
}
