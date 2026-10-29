<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Shop\Cart;
use App\Domain\Shop\Enums\Fulfilment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CheckoutRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * The shop, as on the website. The app keeps the cart on the phone (variant id + quantity) and asks
 * POST /shop/cart for current prices and stock; the order is placed with POST /shop/orders.
 */
class ShopController extends Controller
{
    /**
     * Shop categories.
     *
     * @unauthenticated
     *
     * @return array{data: list<array{slug: string, name: string}>}
     */
    public function categories(): array
    {
        return ['data' => ProductCategory::query()->orderBy('position')->orderBy('name')->get()
            ->map(fn (ProductCategory $category) => ['slug' => $category->slug, 'name' => $category->name])
            ->values()->all()];
    }

    /**
     * Products.
     *
     * Products on sale in the shop, in the shop's order; `?category=slug` filters.
     *
     * @unauthenticated
     */
    public function products(Request $request): AnonymousResourceCollection
    {
        $request->validate(['category' => ['sometimes', 'string', Rule::exists('product_categories', 'slug')]]);

        $products = Product::active()
            ->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($q) => $q->where('slug', $request->query('category'))))
            ->with(['category', 'images', 'variants'])
            ->orderBy('position')->orderByDesc('id')
            ->get();

        return ProductResource::collection($products);
    }

    /**
     * One product.
     *
     * Hidden or deleted products give 404.
     *
     * @unauthenticated
     */
    public function product(Product $product): ProductResource
    {
        abort_unless($product->is_active, 404);

        return new ProductResource($product->load(['category', 'images', 'variants']));
    }

    /**
     * Check my cart.
     *
     * Send the cart kept on the phone; get the current prices, stock and totals. Items no longer on sale
     * are listed in `unavailable` (remove them). `in_stock` false: lower the quantity to `stock`.
     * `can_checkout` is true when every line can be ordered.
     *
     * @unauthenticated
     *
     * @return array{data: array<string, mixed>}
     */
    public function cart(Request $request): array
    {
        $request->validate(CheckoutRequest::itemRules());
        $quantities = CheckoutRequest::quantitiesFrom((array) $request->input('items'));

        $lines = Cart::linesFor($quantities);
        $subtotal = (int) $lines->sum('line_cents');
        $found = $lines->map(fn (array $line) => $line['variant']->id)->all();

        return ['data' => [
            'lines' => $lines->map(fn (array $line) => [
                'variant_id' => $line['variant']->id,
                'variant_name' => $line['variant']->name,
                'product' => [
                    'slug' => $line['variant']->product->slug,
                    'name' => $line['variant']->product->name,
                    'image' => $line['variant']->product->coverUrl(),
                ],
                'quantity' => $line['quantity'],
                'stock' => $line['variant']->stock,
                'in_stock' => $line['in_stock'],
                'unit_price' => ['cents' => $line['unit_cents'], 'formatted' => Product::money($line['unit_cents'])],
                'line_total' => ['cents' => $line['line_cents'], 'formatted' => Product::money($line['line_cents'])],
            ])->values()->all(),
            'unavailable' => array_values(array_diff(array_keys($quantities), $found)),
            'subtotal' => ['cents' => $subtotal, 'formatted' => Product::money($subtotal)],
            'can_checkout' => $lines->isNotEmpty() && $lines->every(fn (array $line) => $line['in_stock']) && count($found) === count($quantities),
        ]];
    }

    /**
     * Checkout form.
     *
     * Pickup or delivery (with fee and note), payment methods, and the member's details to fill in.
     *
     * @unauthenticated
     *
     * @return array{data: array<string, mixed>}
     */
    public function checkoutForm(Request $request): array
    {
        /** @var ?User $user */
        $user = $request->user();
        $hiker = $user?->hiker;
        $fee = (int) config('shop.delivery_fee_cents');

        return ['data' => [
            'fulfilment' => [
                ['key' => Fulfilment::Pickup->value, 'label' => Fulfilment::Pickup->label(), 'fee' => ['cents' => 0, 'formatted' => 'Free'], 'note' => (string) config('shop.pickup_note'), 'needs_address' => false],
                ['key' => Fulfilment::Delivery->value, 'label' => Fulfilment::Delivery->label(), 'fee' => ['cents' => $fee, 'formatted' => Product::money($fee)], 'note' => (string) config('shop.delivery_note'), 'needs_address' => true],
            ],
            'payment_methods' => array_map(fn (PaymentMethod $method) => [
                'key' => $method->value,
                'label' => $method === PaymentMethod::Cash ? 'Cash on delivery / at pickup' : $method->label(),
            ], PaymentMethod::available()),
            'terms' => 'I agree that the club contacts me about this order by phone, WhatsApp or email.',
            'max_quantity' => (int) config('shop.max_quantity'),
            'member' => $user === null ? null : [
                'name' => $hiker->name ?? $user->name,
                'phone' => $hiker->phone ?? $user->phone,
                'email' => $hiker->email ?? $user->email,
            ],
        ]];
    }
}
