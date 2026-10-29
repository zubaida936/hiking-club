<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Payments\Actions\StartOnlinePayment;
use App\Domain\Shop\Actions\ManageOrder;
use App\Domain\Shop\Actions\PlaceOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CheckoutRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Placing a shop order and paying for it, with the website's rules (PlaceOrder, payments actions).
 */
class OrderController extends Controller
{
    /**
     * Place an order.
     *
     * The website's checkout fields (`fulfilment` pickup or delivery with `delivery_city` and
     * `delivery_address`, `payment_method`, `terms` true) plus `items` from the phone's cart. Prices and
     * stock are taken from the shop at this moment; not enough stock answers 422 with a message.
     *
     * @unauthenticated
     */
    public function store(CheckoutRequest $request, PlaceOrder $action): JsonResponse
    {
        $order = $action->placeItems($request->quantities(), $request->toData());

        return (new OrderResource($order->load('items')))->response()->setStatusCode(201);
    }

    /**
     * One order.
     *
     * Opened with its secret token (from placing the order, or the order email link).
     *
     * @unauthenticated
     */
    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load('items'));
    }

    /**
     * Start an online payment.
     *
     * When `payment.instructions.type` is `online`: open `checkout_url` in the browser, then reload the order.
     *
     * @unauthenticated
     *
     * @return array{checkout_url: string}
     */
    public function pay(Order $order, StartOnlinePayment $action): array
    {
        return ['checkout_url' => $action->forOrder($order)];
    }

    /**
     * Report a Whish / OMT transfer.
     *
     * @unauthenticated
     */
    public function transfer(Request $request, Order $order, ManageOrder $action): OrderResource
    {
        $validated = $request->validate(['payment_reference' => ['required', 'string', 'max:60']]);

        $action->reportTransfer($order, trim($validated['payment_reference']));

        return new OrderResource($order->load('items'));
    }
}
