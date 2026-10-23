<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Trail Cap', 'Club T-shirt', 'Rain Jacket', 'Day Pack 25L', 'Trail Socks', 'Buff Neck Gaiter', 'Hiking Boots']).' '.fake()->unique()->numberBetween(1, 9999);

        return [
            'product_category_id' => fn () => ProductCategory::query()->value('id') ?? ProductCategory::create(['name' => 'Accessories', 'slug' => 'accessories'])->id,
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'price_cents' => fake()->randomElement([1500, 2500, 4500, 8000]),
            'sale_price_cents' => null,
            'tags' => [],
            'is_active' => true,
        ];
    }

    /**
     * One "One size" variant with this stock (or several sizes: ['S' => 2, 'M' => 0]).
     *
     * @param  int|array<string, int>  $stock
     */
    public function withStock(int|array $stock = 10): static
    {
        return $this->afterCreating(function (Product $product) use ($stock) {
            foreach (is_array($stock) ? $stock : ['One size' => $stock] as $name => $quantity) {
                ProductVariant::create(['product_id' => $product->id, 'name' => $name, 'stock' => $quantity]);
            }
        });
    }
}
