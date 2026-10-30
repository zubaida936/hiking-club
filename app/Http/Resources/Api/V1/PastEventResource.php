<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Event;
use Illuminate\Http\Request;

/**
 * A past hike in the archive: the list fields plus its photo album. Load `album_slug` with
 * EventController::past()'s subquery (the newest published album with photos).
 *
 * @mixin Event
 */
class PastEventResource extends EventResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $album = $this->getAttribute('album_slug');

        // Spread, not `+`: the API docs (and the app's types) only follow the spread.
        return [
            ...parent::toArray($request),
            // The hike's photo album (GET /gallery/{slug}), null when none is published yet.
            'album_slug' => $album === null ? null : (string) $album,
        ];
    }
}
