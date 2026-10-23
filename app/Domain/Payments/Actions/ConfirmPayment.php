<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Gateways\Gateways;
use App\Domain\Registrations\Events\RegistrationChanged;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * Asks the gateway how an online payment went and records it. A success marks
 * the booking paid (which also lets it earn points). Safe to run more than once:
 * the return page and the provider's webhook may both call it.
 */
final class ConfirmPayment
{
    public function __construct(private readonly Gateways $gateways) {}

    public function handle(Payment $payment): PaymentStatus
    {
        if ($payment->status->isFinal()) {
            return $payment->status;
        }

        $result = $this->gateways->for($payment)->fetchStatus($payment);
        if ($result['status'] === PaymentStatus::Pending) {
            return PaymentStatus::Pending;
        }

        $registration = DB::transaction(function () use ($payment, $result) {
            /** @var Payment $payment */
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($payment->status->isFinal()) {
                return null;
            }

            $payment->update([
                'status' => $result['status'],
                'gateway_reference' => $result['reference'],
                'paid_at' => $result['status'] === PaymentStatus::Succeeded ? now() : null,
            ]);

            if ($result['status'] !== PaymentStatus::Succeeded) {
                return null;
            }

            // A shop order payment.
            if ($payment->order_id !== null) {
                $order = $payment->order;
                $order->update([
                    'paid_at' => $order->paid_at ?? now(),
                    'payment_method' => PaymentMethod::Online,
                    'payment_reference' => $result['reference'],
                ]);

                return null;
            }

            $registration = $payment->registration;
            $registration->update([
                'paid_at' => $registration->paid_at ?? now(),
                'payment_method' => PaymentMethod::Online,
                'payment_reference' => $result['reference'],
            ]);

            return $registration;
        });

        if ($registration !== null) {
            RegistrationChanged::dispatch($registration);
        }

        return $result['status'];
    }
}
