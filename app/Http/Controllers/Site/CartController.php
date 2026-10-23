<?php

namespace App\Http\Controllers\Site;

use App\Domain\Shop\Cart;
use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function show(Cart $cart): View
    {
        $lines = $cart->lines();

        return view('site.shop.cart', [
            'lines' => $lines,
            'subtotal' => (int) $lines->sum('line_cents'),
            'allInStock' => $lines->every(fn (array $line) => $line['in_stock']),
        ]);
    }

    public function add(Request $request, Cart $cart): RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.config('shop.max_quantity')],
        ], ['variant_id.required' => 'Choose a size.']);

        $variant = ProductVariant::query()->whereKey($validated['variant_id'])->whereHas('product', fn ($q) => $q->active())->with('product')->first();

        if ($variant === null || $variant->stock === 0) {
            return back()->with('error', 'Sorry, this size is out of stock.');
        }

        $inCart = $cart->raw()[$variant->id] ?? 0;
        if ($inCart + (int) $validated['quantity'] > $variant->stock) {
            return back()->with('error', "Only {$variant->stock} left in this size".($inCart ? " ({$inCart} already in your cart)." : '.'));
        }

        $cart->add($variant->id, (int) $validated['quantity']);

        return back()->with('success', "{$variant->product->name} added to your cart.");
    }

    public function update(Request $request, Cart $cart): RedirectResponse
    {
        $quantities = (array) $request->validate([
            'quantities' => ['required', 'array'],
            'quantities.*' => ['integer', 'min:0', 'max:'.config('shop.max_quantity')],
        ])['quantities'];

        foreach ($quantities as $variantId => $quantity) {
            $cart->set((int) $variantId, (int) $quantity);
        }

        return redirect()->route('cart')->with('success', 'Cart updated.');
    }

    public function remove(ProductVariant $variant, Cart $cart): RedirectResponse
    {
        $cart->remove($variant->id);

        return redirect()->route('cart');
    }
}
