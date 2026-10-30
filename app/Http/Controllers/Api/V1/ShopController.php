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
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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
     */
    public function cart(Request $request): JsonResponse
    {
        $request->validate(CheckoutRequest::itemRules());
        $quantities = CheckoutRequest::quantitiesFrom((array) $request->input('items'));

        // Loops and casts (not collection maps over plain arrays) so the API docs type every field.
        $cartLines = Cart::linesFor($quantities);
        $subtotal = (int) $cartLines->sum('line_cents');
        $lines = [];
        $found = [];
        $canCheckout = true;
        foreach ($cartLines as $line) {
            /** @var ProductVariant $variant */
            $variant = $line['variant'];
            $unit = (int) $line['unit_cents'];
            $total = (int) $line['line_cents'];
            $inStock = (bool) $line['in_stock'];
            $image = $variant->product->coverUrl();

            $found[] = $variant->id;
            $canCheckout = $canCheckout && $inStock;
            $lines[] = [
                'variant_id' => (int) $variant->id,
                'variant_name' => (string) $variant->name,
                'product' => [
                    'slug' => (string) $variant->product->slug,
                    'name' => (string) $variant->product->name,
                    'image' => $image === null ? null : (string) $image,
                ],
                'quantity' => (int) $line['quantity'],
                'stock' => (int) $variant->stock,
                'in_stock' => $inStock,
                'unit_price' => ['cents' => $unit, 'formatted' => Product::money($unit)],
                'line_total' => ['cents' => $total, 'formatted' => Product::money($total)],
            ];
        }

        $unavailable = [];
        foreach (array_diff(array_keys($quantities), $found) as $variantId) {
            $unavailable[] = (int) $variantId;
        }

        return response()->json(['data' => [
            'lines' => $lines,
            'unavailable' => $unavailable,
            'subtotal' => ['cents' => $subtotal, 'formatted' => Product::money($subtotal)],
            'can_checkout' => $lines !== [] && $canCheckout && $unavailable === [],
        ]]);
    }

    /**
     * Checkout form.
     *
     * Pickup or delivery (with fee and note), payment methods, and the member's details to fill in.
     *
     * @unauthenticated
     */
    public function checkoutForm(Request $request): JsonResponse
    {
        /** @var ?User $user */
        $user = $request->user();
        $hiker = $user?->hiker;
        $fee = (int) config('shop.delivery_fee_cents');

        $methods = [];
        foreach (PaymentMethod::available() as $method) {
            $methods[] = ['key' => $method->value, 'label' => $method === PaymentMethod::Cash ? 'Cash on delivery / at pickup' : $method->label()];
        }

        return response()->json(['data' => [
            'fulfilment' => [
                ['key' => Fulfilment::Pickup->value, 'label' => Fulfilment::Pickup->label(), 'fee' => ['cents' => 0, 'formatted' => 'Free'], 'note' => (string) config('shop.pickup_note'), 'needs_address' => false],
                ['key' => Fulfilment::Delivery->value, 'label' => Fulfilment::Delivery->label(), 'fee' => ['cents' => $fee, 'formatted' => Product::money($fee)], 'note' => (string) config('shop.delivery_note'), 'needs_address' => true],
            ],
            'payment_methods' => $methods,
            'terms' => 'I agree that the club contacts me about this order by phone, WhatsApp or email.',
            'max_quantity' => (int) config('shop.max_quantity'),
            'member' => $user === null ? null : [
                'name' => $hiker->name ?? $user->name,
                'phone' => $hiker->phone ?? $user->phone,
                'email' => $hiker->email ?? $user->email,
            ],
        ]]);
    }
}
