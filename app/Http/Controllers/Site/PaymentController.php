<?php

namespace App\Http\Controllers\Site;

use App\Domain\Payments\Actions\ConfirmPayment;
use App\Domain\Payments\Actions\ReportTransfer;
use App\Domain\Payments\Actions\StartOnlinePayment;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Exceptions\PaymentException;
use App\Http\Controllers\Controller;
use App\Models\EventRegistration;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Paying for a booking from its confirmation page (the secret booking link).
 */
class PaymentController extends Controller
{
    public function pay(EventRegistration $registration, StartOnlinePayment $action): RedirectResponse
    {
        try {
            return redirect()->away($action->handle($registration));
        } catch (PaymentException $e) {
            return redirect()->route('registrations.show', $registration)->with('error', $e->getMessage());
        }
    }

    public function transfer(Request $request, EventRegistration $registration, ReportTransfer $action): RedirectResponse
    {
        $validated = $request->validate(['payment_reference' => ['required', 'string', 'max:60']]);

        try {
            $action->handle($registration, trim($validated['payment_reference']));
        } catch (PaymentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Thanks! We'll confirm your payment soon.");
    }

    /**
     * Where the provider sends the hiker back. The outcome is checked with the provider.
     */
    public function return(Payment $payment, ConfirmPayment $action): RedirectResponse
    {
        $status = $action->handle($payment);

        return redirect()->route('registrations.show', $payment->registration)->with(...match ($status) {
            PaymentStatus::Succeeded => ['status', 'Payment received. Thank you!'],
            PaymentStatus::Failed => ['error', 'The payment did not go through. You can try again or choose another way to pay.'],
            PaymentStatus::Pending => ['status', "We're waiting for the payment provider to confirm your payment. This page will show it once it's done."],
        });
    }

    /**
     * Server-to-server notification from the provider. Only identifies the payment:
     * its status is always fetched from the provider, never taken from the request.
     */
    public function webhook(Request $request, string $gateway, ConfirmPayment $action): Response
    {
        $payment = Payment::where('gateway', $gateway)
            ->where('external_id', (string) ($request->input('external_id') ?? $request->input('externalId')))
            ->first();

        if ($payment !== null) {
            $action->handle($payment);
        }

        return response('OK');
    }
}
