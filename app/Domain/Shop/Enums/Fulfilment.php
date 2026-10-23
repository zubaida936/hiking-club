<?php

namespace App\Domain\Shop\Enums;

enum Fulfilment: string
{
    case Pickup = 'pickup';
    case Delivery = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'Pickup at a hike',
            self::Delivery => 'Delivery',
        };
    }
}
