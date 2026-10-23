<?php

namespace App\Domain\Shop\Data;

use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Shop\Enums\Fulfilment;

/**
 * Checkout form answers. The phone is normalized.
 */
final readonly class CheckoutData
{
    public function __construct(
        public string $name,
        public string $phone,
        public ?string $email,
        public Fulfilment $fulfilment,
        public ?string $deliveryCity,
        public ?string $deliveryAddress,
        public ?string $notes,
        public ?PaymentMethod $paymentMethod,
    ) {}
}
