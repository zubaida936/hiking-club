<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Members\Actions\ResolveHiker;
use App\Domain\Shop\Cart;
use App\Domain\Shop\Data\CheckoutData;
use App\Domain\Shop\Enums\Fulfilment;
use App\Domain\Shop\Enums\OrderStatus;
use App\Domain\Shop\Exceptions\ShopException;
use App\Mail\NewOrderMail;
use App\Mail\OrderPlacedMail;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Support\ReferenceCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Turns the cart into an order. Variants are locked while stock is checked and
 * taken, so two customers can never buy the last item. Prices come from the
 * products at this moment, never from the browser.
 */
final class PlaceOrder
{
    public function __construct(private readonly ResolveHiker $resolveHiker) {}

    /**
     * @throws ShopException
     */
    public function handle(Cart $cart, CheckoutData $data): Order
    {
        $order = $this->placeItems($cart->raw(), $data);
        $cart->clear();

        return $order;
    }

    /**
     * Same as handle(), for a variant id => quantity list (the app's cart, kept on the phone).
     *
     * @param  array<int, int>  $quantities
     *
     * @throws ShopException
     */
    public function placeItems(array $quantities, CheckoutData $data): Order
    {
        if ($quantities === []) {
            throw ShopException::cartEmpty();
        }

        $order = DB::transaction(function () use ($quantities, $data) {
            // Lock in id order so concurrent checkouts can't deadlock.
            $variants = ProductVariant::query()
                ->whereIn('id', array_keys($quantities))
                ->whereHas('product', fn ($q) => $q->active())
                ->with('product')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($variants->isEmpty()) {
                throw ShopException::cartEmpty();
            }

            $lines = [];
            foreach ($variants as $variant) {
                $quantity = $quantities[$variant->id];
                if ($quantity > $variant->stock) {
                    throw ShopException::notEnoughStock($this->itemName($variant), $variant->stock);
                }
                $lines[] = [$variant, $quantity, $variant->product->currentPriceCents()];
            }

            $subtotal = array_sum(array_map(fn (array $line) => $line[1] * $line[2], $lines));
            $deliveryFee = $data->fulfilment === Fulfilment::Delivery ? (int) config('shop.delivery_fee_cents') : 0;
            $hiker = $this->resolveHiker->handle($data->phone, $data->name, $data->email);

            $order = Order::create([
                'reference' => ReferenceCode::unique(Order::class),
                'token' => Str::random(64),
                'status' => OrderStatus::New,
                'hiker_id' => $hiker->id,
                'name' => $data->name,
                'phone' => $data->phone,
                'email' => $data->email,
                'fulfilment' => $data->fulfilment,
                'delivery_city' => $data->fulfilment === Fulfilment::Delivery ? $data->deliveryCity : null,
                'delivery_address' => $data->fulfilment === Fulfilment::Delivery ? $data->deliveryAddress : null,
                'notes' => $data->notes,
                'subtotal_cents' => $subtotal,
                'delivery_fee_cents' => $deliveryFee,
                'total_cents' => $subtotal + $deliveryFee,
                'payment_method' => $data->paymentMethod,
            ]);

            foreach ($lines as [$variant, $quantity, $unit]) {
                $order->items()->create([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_name' => $variant->name,
                    'unit_price_cents' => $unit,
                    'quantity' => $quantity,
                ]);
                $variant->decrement('stock', $quantity);
            }

            return $order;
        });

        if ($order->email !== null) {
            Mail::to($order->email)->queue(new OrderPlacedMail($order));
        }
        if (filled(config('shop.notify_email'))) {
            Mail::to((string) config('shop.notify_email'))->queue(new NewOrderMail($order));
        }

        return $order;
    }

    private function itemName(ProductVariant $variant): string
    {
        return $variant->product->name.($variant->name !== 'One size' ? " ({$variant->name})" : '');
    }
}
