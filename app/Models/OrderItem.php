<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property ?int $product_id
 * @property ?int $product_variant_id
 * @property string $product_name
 * @property string $variant_name
 * @property int $unit_price_cents
 * @property int $quantity
 */
class OrderItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['unit_price_cents' => 'integer', 'quantity' => 'integer'];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function lineTotalCents(): int
    {
        return $this->unit_price_cents * $this->quantity;
    }
}
