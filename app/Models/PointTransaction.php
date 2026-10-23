<?php

namespace App\Models;

use App\Domain\Loyalty\Enums\PointType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One line of a hiker's points ledger. Never edited: corrections are new lines.
 *
 * @property int $id
 * @property int $hiker_id
 * @property ?int $event_registration_id
 * @property PointType $type
 * @property int $points
 * @property string $description
 * @property ?int $created_by
 * @property Carbon $created_at
 */
class PointTransaction extends Model
{
    /**
     * Written only by the Loyalty domain actions.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => PointType::class,
            'points' => 'integer',
        ];
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
