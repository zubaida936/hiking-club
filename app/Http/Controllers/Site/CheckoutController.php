<?php

namespace App\Http\Controllers\Site;

use App\Domain\Payments\Actions\StartOnlinePayment;
use App\Domain\Payments\Exceptions\PaymentException;
use App\Domain\Shop\Actions\ManageOrder;
use App\Domain\Shop\Actions\PlaceOrder;
use App\Domain\Shop\Cart;
use App\Domain\Shop\Exceptions\ShopException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\CheckoutRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Checkout and the customer's order page (reached through the secret order link).
 */
class CheckoutController extends Controller
{
    public function create(Request $request, Cart $cart): View|RedirectResponse
    {
        $lines = $cart->lines();
        if ($lines->isEmpty()) {
            return redirect()->route('cart');
        }

        return view('site.shop.checkout', [
            'lines' => $lines,
            'subtotal' => (int) $lines->sum('line_cents'),
            'member' => $request->user()?->hiker,
        ]);
    }

    public function store(CheckoutRequest $request, Cart $cart, PlaceOrder $action): RedirectResponse
    {
        try {
            $order = $action->handle($cart, $request->toData());
        } catch (ShopException $e) {
            return redirect()->route('cart')->with('error', $e->getMessage());
        }

        return redirect()->route('orders.show', $order);
    }

    public function show(Order $order): View
    {
        return view('site.shop.order', ['order' => $order->load('items')]);
    }

    public function pay(Order $order, StartOnlinePayment $action): RedirectResponse
    {
        try {
            return redirect()->away($action->forOrder($order));
        } catch (PaymentException $e) {
            return redirect()->route('orders.show', $order)->with('error', $e->getMessage());
        }
    }

    public function transfer(Request $request, Order $order, ManageOrder $action): RedirectResponse
    {
        $validated = $request->validate(['payment_reference' => ['required', 'string', 'max:60']]);

        try {
            $action->reportTransfer($order, trim($validated['payment_reference']));
        } catch (PaymentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Thanks! We'll confirm your payment soon.");
    }
}
