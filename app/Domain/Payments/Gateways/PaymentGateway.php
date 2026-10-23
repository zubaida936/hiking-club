<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Enums\PaymentStatus;
use App\Models\Payment;

/**
 * An online payment provider. To add one (Whish Money, OMT Pay, a bank gateway):
 * implement this interface with the provider's API and register the class in
 * config/payments.php → gateways.
 */
interface PaymentGateway
{
    /**
     * Creates the payment at the provider and returns the page to send the hiker to.
     * The provider sends the hiker back to $returnUrl when they are done.
     */
    public function startCheckout(Payment $payment, string $returnUrl): string;

    /**
     * Asks the provider for the payment's status. Return URLs and webhooks only
     * trigger this check: they are never trusted on their own.
     *
     * @return array{status: PaymentStatus, reference: ?string}
     */
    public function fetchStatus(Payment $payment): array;
}
