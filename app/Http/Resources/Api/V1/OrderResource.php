<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Shop\Enums\Fulfilment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A shop order, as on the website's order page (the secret link). Load items first.
 * `token` opens the order (GET /orders/{token}): the app keeps it, like the customer keeps the link.
 *
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Links to products still on sale (one query for all items).
        $slugs = Product::active()->whereIn('id', $this->items->pluck('product_id')->filter())->pluck('slug', 'id');

        return [
            'reference' => $this->reference,
            'token' => $this->token,
            // new (Received), preparing, ready (Ready for pickup / Out for delivery), completed, cancelled.
            'status' => ['key' => $this->status->value, 'label' => $this->statusLabel()],
            'items' => $this->items->map(fn (OrderItem $item) => [
                'product_slug' => $slugs->get((int) $item->product_id),
                'product_name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'quantity' => $item->quantity,
                'unit_price' => ['cents' => $item->unit_price_cents, 'formatted' => Product::money($item->unit_price_cents)],
                'line_total' => ['cents' => $item->lineTotalCents(), 'formatted' => Product::money($item->lineTotalCents())],
            ])->values()->all(),
            'subtotal' => ['cents' => $this->subtotal_cents, 'formatted' => Product::money($this->subtotal_cents)],
            'delivery_fee' => ['cents' => $this->delivery_fee_cents, 'formatted' => Product::money($this->delivery_fee_cents)],
            'total' => ['cents' => $this->total_cents, 'formatted' => Product::money($this->total_cents)],
            'fulfilment' => [
                'key' => $this->fulfilment->value,
                'label' => $this->fulfilment->label(),
                'city' => $this->delivery_city,
                'address' => $this->delivery_address,
                'note' => (string) config($this->fulfilment === Fulfilment::Delivery ? 'shop.delivery_note' : 'shop.pickup_note'),
            ],
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'notes' => $this->notes,
            'payment' => $this->payment(),
            'cancellation' => $this->isCancelled() ? ['cancelled_at' => $this->cancelled_at?->toIso8601String(), 'reason' => $this->cancellation_reason] : null,
            'ordered_at' => $this->created_at->toIso8601String(),
            'web_url' => route('orders.show', $this->resource),
        ];
    }

    /**
     * Same states and instruction types as a booking's payment: `state` none / paid / due; when due,
     * `instructions.type` online (POST /orders/{token}/pay), transfer (POST /orders/{token}/transfer),
     * cash or contact.
     *
     * @return array<string, mixed>
     */
    private function payment(): array
    {
        $method = $this->payment_method;
        $state = match (true) {
            $this->total_cents === 0 || $this->isCancelled() => 'none',
            $this->paid_at !== null => 'paid',
            default => 'due',
        };

        return [
            'state' => $state,
            'method' => $method === null ? null : ['key' => $method->value, 'label' => $method->label()],
            'paid_at' => $this->paid_at?->toIso8601String(),
            'instructions' => $state === 'due' ? $this->instructions($method) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function instructions(?PaymentMethod $method): array
    {
        $total = Product::money($this->total_cents);

        return match (true) {
            $method === PaymentMethod::Online && PaymentMethod::Online->isAvailable() => ['type' => 'online'],
            (bool) $method?->isWalletTransfer() => [
                'type' => 'transfer',
                'provider' => $method === PaymentMethod::Whish ? 'Whish Money' : 'OMT Pay',
                'account_name' => $method->accountName(),
                'account_number' => $method->accountNumber(),
                'note' => $this->reference,
                'reported_transaction_number' => $this->payment_reference,
            ],
            $method === PaymentMethod::Cash => [
                'type' => 'cash',
                'message' => "Pay {$total} in cash ".($this->fulfilment === Fulfilment::Delivery ? 'to the driver.' : 'when you collect your order.'),
            ],
            default => ['type' => 'contact', 'message' => (string) config('site.booking.payment_note')],
        };
    }
}
