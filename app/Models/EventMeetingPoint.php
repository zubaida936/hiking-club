<?php

namespace App\Models;

use Database\Factories\EventMeetingPointFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property string $pickup_time HH:MM:SS, local time
 * @property int $position
 */
#[Fillable(['name', 'pickup_time', 'position'])]
class EventMeetingPoint extends Model
{
    /** @use HasFactory<EventMeetingPointFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function pickupTimeShort(): string
    {
        return substr($this->pickup_time, 0, 5);
    }
}
