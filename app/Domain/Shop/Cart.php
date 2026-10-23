<?php

namespace App\Domain\Shop;

use App\Models\ProductVariant;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * The visitor's cart, kept in the session: variant id => quantity.
 * Stock is checked again (and reserved) when the order is placed.
 */
final class Cart
{
    private const KEY = 'shop.cart';

    public function __construct(private readonly Session $session) {}

    public function add(int $variantId, int $quantity): void
    {
        $items = $this->raw();
        $this->put($variantId, ($items[$variantId] ?? 0) + $quantity);
    }

    public function set(int $variantId, int $quantity): void
    {
        $this->put($variantId, $quantity);
    }

    public function remove(int $variantId): void
    {
        $items = $this->raw();
        unset($items[$variantId]);
        $this->session->put(self::KEY, $items);
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
    }

    public function count(): int
    {
        return (int) array_sum($this->raw());
    }

    /**
     * Cart lines for products still on sale in the shop, in the order they were added.
     *
     * @return Collection<int, array{variant: ProductVariant, quantity: int, unit_cents: int, line_cents: int, in_stock: bool}>
     */
    public function lines(): Collection
    {
        $items = $this->raw();
        if ($items === []) {
            return collect();
        }

        $variants = ProductVariant::query()
            ->whereIn('id', array_keys($items))
            ->whereHas('product', fn ($q) => $q->active())
            ->with('product.images')
            ->get()
            ->keyBy('id');

        return collect($items)
            ->filter(fn (int $quantity, int $id) => $variants->has($id))
            ->map(function (int $quantity, int $id) use ($variants) {
                /** @var ProductVariant $variant */
                $variant = $variants->get($id);
                $unit = $variant->product->currentPriceCents();

                return [
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'unit_cents' => $unit,
                    'line_cents' => $unit * $quantity,
                    'in_stock' => $quantity <= $variant->stock,
                ];
            })
            ->values();
    }

    /**
     * @return array<int, int>
     */
    public function raw(): array
    {
        return array_map('intval', (array) $this->session->get(self::KEY, []));
    }

    private function put(int $variantId, int $quantity): void
    {
        $items = $this->raw();
        $quantity = min($quantity, (int) config('shop.max_quantity'));

        if ($quantity <= 0) {
            unset($items[$variantId]);
        } else {
            $items[$variantId] = $quantity;
        }

        $this->session->put(self::KEY, $items);
    }
}
