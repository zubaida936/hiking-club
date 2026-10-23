<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Facades\Cache;

/**
 * Simulated provider for development and testing: the "checkout page" is a page of
 * this site with Pay / Fail buttons (Site\TestPaymentController). Never use in production.
 */
final class TestGateway implements PaymentGateway
{
    public function startCheckout(Payment $payment, string $returnUrl): string
    {
        Cache::put($this->key($payment, 'return'), $returnUrl, now()->addDay());

        return route('payments.test.show', $payment->external_id);
    }

    public function fetchStatus(Payment $payment): array
    {
        $outcome = Cache::get($this->key($payment, 'outcome'));

        return [
            'status' => PaymentStatus::tryFrom((string) $outcome) ?? PaymentStatus::Pending,
            'reference' => $outcome === PaymentStatus::Succeeded->value ? 'TEST-'.substr($payment->external_id, 0, 8) : null,
        ];
    }

    /**
     * What the simulator page records when the tester clicks Pay or Fail; returns where to send them.
     */
    public function simulate(Payment $payment, PaymentStatus $outcome): string
    {
        Cache::put($this->key($payment, 'outcome'), $outcome->value, now()->addDay());

        return (string) Cache::get($this->key($payment, 'return'), route('home'));
    }

    private function key(Payment $payment, string $what): string
    {
        return "test-gateway:{$payment->external_id}:{$what}";
    }
}
