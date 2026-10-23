<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One use of a rank reward (config/rewards.php key in `reward`).
 *
 * @property int $id
 * @property int $hiker_id
 * @property string $reward
 * @property ?int $event_registration_id
 * @property int $discount_cents
 * @property ?string $note
 * @property ?int $created_by
 * @property ?Carbon $released_at
 * @property Carbon $created_at
 */
class RewardRedemption extends Model
{
    /**
     * Written only by the Rewards domain actions.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'discount_cents' => 'integer',
            'released_at' => 'datetime',
        ];
    }

    /**
     * Uses that count (not given back after a cancellation).
     *
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('released_at');
    }

    /**
     * @return BelongsTo<Hiker, $this>
     */
    public function hiker(): BelongsTo
    {
        return $this->belongsTo(Hiker::class);
    }

    /**
     * @return BelongsTo<EventRegistration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'event_registration_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
