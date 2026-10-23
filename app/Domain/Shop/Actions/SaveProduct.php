<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Data\ProductData;
use App\Models\Product;
use App\Support\ImageOptimizer;
use App\Support\UniqueSlug;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Creates or updates a product with its variants (sizes) and photos.
 * Variants removed in the CMS are deleted (past orders keep their copied names).
 */
final class SaveProduct
{
    private const IMAGE_DIR = 'products';

    public function handle(ProductData $data, ?Product $product = null): Product
    {
        $product ??= new Product;

        $stored = array_values(array_filter(array_map(
            fn ($file) => ImageOptimizer::storeJpeg($file, self::IMAGE_DIR, maxSize: 1600),
            $data->newImages,
        )));

        $removedPaths = [];

        DB::transaction(function () use ($data, $product, $stored, &$removedPaths) {
            $product->fill([
                'product_category_id' => $data->categoryId,
                'name' => $data->name,
                // An empty slug keeps the current one, so shop links stay stable.
                'slug' => UniqueSlug::for(Product::class, $data->slug ?: ($product->exists ? $product->slug : $data->name), $product->id),
                'description' => $data->description,
                'price_cents' => $data->priceCents,
                'sale_price_cents' => $data->salePriceCents,
                'tags' => $data->tags,
                'is_active' => $data->isActive,
            ])->save();

            // Variants: update the ones kept, add new ones, delete the rest.
            $keep = [];
            foreach ($data->variants as $position => $row) {
                $variant = $row['id'] !== null ? $product->variants()->find($row['id']) : null;
                $variant ??= $product->variants()->make();
                $variant->fill(['name' => $row['name'], 'stock' => $row['stock'], 'position' => $position])->save();
                $keep[] = $variant->id;
            }
            $product->variants()->whereNotIn('id', $keep)->delete();

            $removed = $product->images()->whereIn('id', $data->removeImageIds)->get();
            $removedPaths = $removed->pluck('path')->all();
            $product->images()->whereIn('id', $removed->modelKeys())->delete();

            $next = (int) $product->images()->max('position') + 1;
            foreach ($stored as $i => $path) {
                $product->images()->create(['path' => $path, 'position' => $next + $i]);
            }
        });

        // Files are removed only once the database change is committed.
        if ($removedPaths !== []) {
            Storage::disk('public')->delete($removedPaths);
        }

        return $product->load(['variants', 'images', 'category']);
    }
}
