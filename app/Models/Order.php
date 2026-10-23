<?php

namespace App\Models;

use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Shop\Enums\Fulfilment;
use App\Domain\Shop\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A shop order.
 *
 * @property int $id
 * @property string $reference
 * @property string $token
 * @property OrderStatus $status
 * @property ?int $hiker_id
 * @property string $name
 * @property string $phone
 * @property ?string $email
 * @property Fulfilment $fulfilment
 * @property ?string $delivery_city
 * @property ?string $delivery_address
 * @property ?string $notes
 * @property int $subtotal_cents
 * @property int $delivery_fee_cents
 * @property int $total_cents
 * @property ?PaymentMethod $payment_method
 * @property ?string $payment_reference
 * @property ?Carbon $paid_at
 * @property ?Carbon $cancelled_at
 * @property ?string $cancellation_reason
 * @property ?int $updated_by
 * @property Carbon $created_at
 */
class Order extends Model
{
    /**
     * Written only by the Shop domain actions.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'fulfilment' => Fulfilment::class,
            'payment_method' => PaymentMethod::class,
            'subtotal_cents' => 'integer',
            'delivery_fee_cents' => 'integer',
            'total_cents' => 'integer',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * The customer's order page is reached through the secret token.
     */
    public function getRouteKeyName(): string
    {
        return 'token';
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return BelongsTo<Hiker, $this>
     */
    public function hiker(): BelongsTo
    {
        return $this->belongsTo(Hiker::class);
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::Cancelled;
    }

    public function awaitsPayment(): bool
    {
        return ! $this->isCancelled() && $this->paid_at === null && $this->total_cents > 0;
    }

    public function statusLabel(): string
    {
        return $this->status->label($this->fulfilment);
    }

    public function itemCount(): int
    {
        return (int) $this->items->sum('quantity');
    }
}
