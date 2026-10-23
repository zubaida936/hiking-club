<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Exceptions\PaymentException;
use App\Domain\Payments\Gateways\Gateways;
use App\Models\EventRegistration;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;

/**
 * Starts an online payment for a confirmed, unpaid booking and returns the
 * provider page to send the hiker to.
 */
final class StartOnlinePayment
{
    public function __construct(private readonly Gateways $gateways) {}

    /**
     * @throws PaymentException
     */
    public function handle(EventRegistration $registration): string
    {
        if (! PaymentMethod::Online->isAvailable()) {
            throw PaymentException::onlineUnavailable();
        }
        if ($registration->paid_at !== null) {
            throw PaymentException::alreadyPaid();
        }
        if (! $registration->isConfirmed() || $registration->amountDueCents() === 0) {
            throw PaymentException::notPayable();
        }

        $payment = $registration->payments()->create([
            'gateway' => (string) config('payments.online_gateway'),
            'external_id' => (string) Str::uuid(),
            'amount_cents' => $registration->amountDueCents(),
            'currency' => config('payments.currency'),
            'status' => PaymentStatus::Pending,
        ]);

        $url = $this->gateways->active()->startCheckout($payment, route('payments.return', $payment->external_id));
        $payment->update(['checkout_url' => $url]);
        $registration->update(['payment_method' => PaymentMethod::Online]);

        return $url;
    }

    /**
     * Same for a shop order.
     *
     * @throws PaymentException
     */
    public function forOrder(Order $order): string
    {
        if (! PaymentMethod::Online->isAvailable()) {
            throw PaymentException::onlineUnavailable();
        }
        if ($order->paid_at !== null) {
            throw PaymentException::alreadyPaid();
        }
        if (! $order->awaitsPayment()) {
            throw PaymentException::notPayable();
        }

        $payment = $order->payments()->create([
            'gateway' => (string) config('payments.online_gateway'),
            'external_id' => (string) Str::uuid(),
            'amount_cents' => $order->total_cents,
            'currency' => config('payments.currency'),
            'status' => PaymentStatus::Pending,
        ]);

        $url = $this->gateways->active()->startCheckout($payment, route('payments.return', $payment->external_id));
        $payment->update(['checkout_url' => $url]);
        $order->update(['payment_method' => PaymentMethod::Online]);

        return $url;
    }
}
