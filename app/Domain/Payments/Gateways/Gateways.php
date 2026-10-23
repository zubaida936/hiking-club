<?php

namespace App\Domain\Payments\Gateways;

use App\Models\Payment;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Finds gateway classes by name (config/payments.php → gateways). A payment is always
 * checked with the gateway that created it, even if the active one changed since.
 */
final class Gateways
{
    public function __construct(private readonly Container $container) {}

    public function active(): PaymentGateway
    {
        return $this->named((string) config('payments.online_gateway'));
    }

    public function for(Payment $payment): PaymentGateway
    {
        return $this->named($payment->gateway);
    }

    private function named(string $name): PaymentGateway
    {
        $class = config("payments.gateways.{$name}");

        if (! is_string($class)) {
            throw new InvalidArgumentException("Unknown payment gateway [{$name}].");
        }

        $gateway = $this->container->make($class);

        if (! $gateway instanceof PaymentGateway) {
            throw new InvalidArgumentException("[{$class}] is not a payment gateway.");
        }

        return $gateway;
    }
}
