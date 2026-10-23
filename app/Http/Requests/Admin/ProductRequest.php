<?php

namespace App\Http\Requests\Admin;

use App\Domain\Shop\Data\ProductData;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');

        return [
            'product_category_id' => ['required', 'integer', Rule::exists('product_categories', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'alpha_dash:ascii', Rule::unique('products', 'slug')->ignore($product)],
            'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['required', 'numeric', 'min:0', 'max:100000', 'decimal:0,2'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:price', 'decimal:0,2'],
            'tags' => ['nullable', 'string', 'max:300'],
            'is_active' => ['boolean'],
            'variants' => ['required', 'array', 'min:1', 'max:30'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.name' => ['required', 'string', 'max:60', 'distinct:ignore_case'],
            'variants.*.stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'product_category_id' => 'category',
            'sale_price' => 'sale price',
            'variants.*.name' => 'size name',
            'variants.*.stock' => 'stock',
            'images.*' => 'photo',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['sale_price.lt' => 'The sale price must be lower than the price.', 'variants.required' => 'Add at least one size (or "One size").'];
    }

    protected function prepareForValidation(): void
    {
        // Empty repeater rows are ignored.
        $rows = collect((array) $this->input('variants', []))
            ->filter(fn ($row) => is_array($row) && filled($row['name'] ?? null))
            ->values()
            ->all();

        $this->merge([
            'variants' => $rows,
            'is_active' => $this->boolean('is_active'),
            'slug' => $this->filled('slug') ? strtolower(trim($this->string('slug')->toString())) : null,
        ]);
    }

    public function toData(): ProductData
    {
        $cents = fn (mixed $value) => (int) round((float) $value * 100);

        return new ProductData(
            categoryId: $this->integer('product_category_id'),
            name: $this->string('name')->squish()->toString(),
            slug: $this->input('slug'),
            description: $this->filled('description') ? $this->string('description')->trim()->toString() : null,
            priceCents: $cents($this->input('price')),
            salePriceCents: $this->filled('sale_price') ? $cents($this->input('sale_price')) : null,
            tags: array_values(array_unique(array_filter(array_map('trim', explode(',', $this->string('tags')->toString()))))),
            isActive: $this->boolean('is_active'),
            variants: array_map(fn (array $row) => [
                'id' => isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : null,
                'name' => trim((string) $row['name']),
                'stock' => (int) $row['stock'],
            ], (array) $this->validated('variants')),
            newImages: array_values((array) $this->file('images', [])),
            removeImageIds: array_map('intval', (array) $this->input('remove_images', [])),
        );
    }
}
