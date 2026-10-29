<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A shop product. Load category, images and variants first. Prices are what the customer pays now
 * (`price`); `regular_price` is set only while the product is on sale (shown struck through).
 *
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $stock = (int) $this->variants->sum('stock');

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => ['slug' => $this->category->slug, 'name' => $this->category->name],
            'description' => $this->description,
            'tags' => $this->tags ?? [],
            'price' => ['cents' => $this->currentPriceCents(), 'formatted' => Product::money($this->currentPriceCents())],
            'regular_price' => $this->isOnSale() ? ['cents' => $this->price_cents, 'formatted' => Product::money($this->price_cents)] : null,
            'stock' => [
                'total' => $stock,
                'sold_out' => $stock === 0,
                // "Only N left" from config shop.low_stock down.
                'low' => $stock > 0 && $stock <= (int) config('shop.low_stock'),
            ],
            'images' => $this->images->map(fn (ProductImage $image) => $image->url())->values()->all(),
            // Sizes / versions: send `variant_id` with the quantity in the cart. Sold-out ones have stock 0.
            'variants' => $this->variants->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'name' => $variant->name,
                'stock' => $variant->stock,
            ])->values()->all(),
            'web_url' => route('shop.show', $this->resource),
        ];
    }
}
