<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An event card: everything the hikes list shows. Load seat counts first
 * (Event::withSeatCounts() / loadSeatCount()) to avoid a query per event.
 *
 * @mixin Event
 */
class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'location' => $this->location,
            'season_tag' => $this->season_tag,
            'starts_on' => $this->starts_on->toDateString(),
            'summary' => $this->summary,
            'distance_km' => $this->distance_km,
            'elevation_gain_m' => $this->elevation_gain_m,
            'elevation_loss_m' => $this->elevation_loss_m,
            'duration_hours' => $this->duration_hours,
            'effort_score' => $this->effort_score,
            'difficulty' => [
                'level' => $this->difficulty_level->value,
                'name' => $this->difficulty_level->name,
                'label' => $this->difficulty_level->label(),
                'description' => $this->difficulty_level->description(),
            ],
            'fee' => ['cents' => $this->fee_cents, 'formatted' => $this->formattedFee()],
            'is_xtreme' => $this->is_xtreme,
            'status' => $this->status->value,
            'cancellation_reason' => $this->isCancelled() ? $this->cancellation_reason : null,
            'seats' => [
                'capacity' => $this->capacity,
                'left' => $this->seatsLeft(),
                'sold_out' => $this->isSoldOut(),
            ],
            'registration' => [
                'open' => $this->isOpenForRegistration(),
                'closes_at' => $this->registrationDeadline()->toIso8601String(),
                'waitlist_open' => $this->acceptsWaitlist(),
                'requires_level_acknowledgement' => $this->requiresLevelAcknowledgement(),
            ],
            'cover_image_url' => $this->coverImageUrl(),
            'web_url' => route('events.show', $this->resource),
        ];
    }
}
