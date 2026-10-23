<?php

namespace App\Http\Controllers\Site;

use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Gateways\TestGateway;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The test gateway's "provider page": lets a tester approve or decline a payment.
 * Only reachable while the test gateway is the active one, and never in production.
 */
class TestPaymentController extends Controller
{
    public function show(Payment $payment): View
    {
        $this->ensureEnabled($payment);

        return view('site.payments.test', ['payment' => $payment->load(['registration.event', 'order'])]);
    }

    public function simulate(Request $request, Payment $payment, TestGateway $gateway): RedirectResponse
    {
        $this->ensureEnabled($payment);

        $outcome = $request->input('outcome') === 'pay' ? PaymentStatus::Succeeded : PaymentStatus::Failed;

        return redirect()->to($gateway->simulate($payment, $outcome));
    }

    private function ensureEnabled(Payment $payment): void
    {
        abort_unless(config('payments.online_gateway') === 'test' && ! app()->isProduction() && $payment->gateway === 'test', 404);
    }
}
