<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Sample shop products (no photos) for local development and manual testing.
 * Skipped when products already exist. Not run in production (see DatabaseSeeder).
 */
class DemoShopSeeder extends Seeder
{
    public function run(): void
    {
        if (Product::withTrashed()->exists()) {
            return;
        }

        /** @var list<array{string, string, int, ?int, list<string>, array<string, int>}> $products */
        $products = [
            ['Club Trail Cap', 'accessories', 1500, null, ['Breathable'], ['One size' => 25]],
            ['Merino Hiking Socks', 'accessories', 1200, 900, ['Merino'], ['S' => 6, 'M' => 3, 'L' => 0]],
            ['Day Pack 25L', 'backpack', 6500, null, ['Rain cover'], ['One size' => 4]],
            ['Club T-shirt', 'clothing', 2000, null, ['Quick dry'], ['S' => 10, 'M' => 12, 'L' => 8, 'XL' => 2]],
            ['Trail Runner Shoes', 'footwear', 9500, 7900, ['Grip'], ['40' => 1, '41' => 3, '42' => 0, '43' => 2]],
            ['Storm Shell Jacket', 'jacket', 12000, null, ['Waterproof', 'Breathable'], ['S' => 0, 'M' => 0]],
        ];

        foreach ($products as $position => [$name, $category, $price, $sale, $tags, $stock]) {
            $product = Product::create([
                'product_category_id' => ProductCategory::where('slug', $category)->value('id'),
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => "Demo product for testing the shop.\n\nPhotos can be added in the CMS → Shop products.",
                'price_cents' => $price,
                'sale_price_cents' => $sale,
                'tags' => $tags,
                'is_active' => true,
                'position' => $position,
            ]);

            foreach (array_keys($stock) as $i => $size) {
                $product->variants()->create(['name' => (string) $size, 'stock' => $stock[$size], 'position' => $i]);
            }
        }
    }
}
