<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A size or colour of a product, with its own stock.
 *
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property int $stock
 * @property int $position
 */
class ProductVariant extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['stock' => 'integer', 'position' => 'integer'];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
