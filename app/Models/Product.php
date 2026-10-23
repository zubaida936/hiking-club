<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $product_category_id
 * @property string $name
 * @property string $slug
 * @property ?string $description
 * @property int $price_cents
 * @property ?int $sale_price_cents
 * @property ?list<string> $tags
 * @property bool $is_active
 * @property int $position
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Written only by the Shop domain actions.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'sale_price_cents' => 'integer',
            'tags' => 'array',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function isOnSale(): bool
    {
        return $this->sale_price_cents !== null && $this->sale_price_cents < $this->price_cents;
    }

    /**
     * What a customer pays per item.
     */
    public function currentPriceCents(): int
    {
        return $this->isOnSale() ? (int) $this->sale_price_cents : $this->price_cents;
    }

    /**
     * Total stock over all variants (uses a loaded variants_sum_stock when present).
     */
    public function totalStock(): int
    {
        return (int) ($this->attributes['variants_sum_stock'] ?? $this->variants->sum('stock'));
    }

    public function coverUrl(): ?string
    {
        $path = $this->relationLoaded('images') ? $this->images->first()?->path : $this->images()->value('path');

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public static function money(int $cents): string
    {
        return '$'.rtrim(rtrim(number_format($cents / 100, 2, '.', ''), '0'), '.');
    }
}
