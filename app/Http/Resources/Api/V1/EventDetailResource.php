<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Event;
use App\Models\EventMeetingPoint;
use App\Models\EventUpdate;
use Illuminate\Http\Request;

/**
 * The full hike page. Load meetingPoints, updates and the seat count first; pass the photo
 * album slug (past hikes with a published album) with withAlbumSlug().
 *
 * @mixin Event
 */
class EventDetailResource extends EventResource
{
    private ?string $albumSlug = null;

    public function withAlbumSlug(?string $slug): static
    {
        $this->albumSlug = $slug;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Spread, not `+`: the API docs (and the app's types) only follow the spread.
        return [
            ...parent::toArray($request),
            // Plain text; a blank line separates paragraphs.
            'description' => $this->description,
            'included' => $this->included ?? [],
            'what_to_bring' => $this->what_to_bring ?? [],
            'itinerary' => $this->itinerarySteps(),
            'meeting_points' => $this->meetingPoints->map(fn (EventMeetingPoint $point) => [
                'name' => $point->name,
                'pickup_time' => $point->pickupTimeShort(),
            ])->all(),
            'updates' => $this->updates->map(fn (EventUpdate $update) => [
                'message' => $update->message,
                'posted_at' => $update->created_at->toIso8601String(),
            ])->all(),
            'photo_credit' => $this->photo_credit,
            'gpx_url' => $this->gpxUrl(),
            // [[lat, lng], ...] for drawing the route; empty when no GPX was uploaded.
            'route_points' => $this->route_points ?? [],
            // Photos of a past hike: GET /gallery/{album_slug}. (Written out so the docs type it string|null.)
            'album_slug' => $this->albumSlug !== null ? (string) $this->albumSlug : null,
        ];
    }
}
