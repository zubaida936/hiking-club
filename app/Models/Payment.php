<?php

namespace App\Models;

use App\Domain\Payments\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One online payment attempt for a booking.
 *
 * @property int $id
 * @property ?int $event_registration_id set for a booking payment
 * @property ?int $order_id set for a shop order payment
 * @property string $gateway
 * @property string $external_id
 * @property ?string $gateway_reference
 * @property int $amount_cents
 * @property string $currency
 * @property PaymentStatus $status
 * @property ?string $checkout_url
 * @property ?Carbon $paid_at
 */
class Payment extends Model
{
    /**
     * Written only by the Payments domain actions.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EventRegistration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'event_registration_id');
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function formattedAmount(): string
    {
        return '$'.rtrim(rtrim(number_format($this->amount_cents / 100, 2, '.', ''), '0'), '.');
    }
}
