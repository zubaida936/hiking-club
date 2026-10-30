<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\EventFilterRequest;
use App\Http\Resources\Api\V1\EventDetailResource;
use App\Http\Resources\Api\V1\EventResource;
use App\Http\Resources\Api\V1\PastEventResource;
use App\Models\Album;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

/**
 * Published hikes, same rules as the website's events pages. Unlike the website, unknown
 * filter values are rejected (422): the app builds them, so a wrong one is a bug to see.
 */
class EventController extends Controller
{
    public const PER_PAGE = 20;

    /**
     * Upcoming hikes.
     *
     * Soonest first. Today's hikes count as upcoming.
     *
     * @unauthenticated
     */
    public function index(EventFilterRequest $request): AnonymousResourceCollection
    {
        $events = Event::published()->upcoming()
            ->where($request->filters())
            ->when($request->has('xtreme'), fn (Builder $q) => $q->where('is_xtreme', $request->boolean('xtreme')))
            ->withSeatCounts()
            ->orderBy('starts_on')->orderBy('id')
            ->paginate(self::PER_PAGE);

        return EventResource::collection($events);
    }

    /**
     * Past hikes.
     *
     * Newest first; filter by month for the archive. `album_slug` links a hike to its photos.
     *
     * @unauthenticated
     */
    public function past(EventFilterRequest $request): AnonymousResourceCollection
    {
        $events = Event::published()->past()
            ->where($request->filters())
            ->withSeatCounts()
            // The newest published album with photos, in the same query (like the hike page's link).
            ->addSelect(['album_slug' => Album::published()->select('slug')->whereColumn('event_id', 'events.id')->has('photos')->latest('id')->limit(1)])
            ->orderByDesc('starts_on')->orderByDesc('id')
            ->paginate(self::PER_PAGE);

        // Built explicitly: the API docs read PastEventResource::collection() as a list of the parent EventResource.
        return new AnonymousResourceCollection($events, PastEventResource::class);
    }

    /**
     * Months with hikes.
     *
     * Every month with published hikes, newest first, for the month filter.
     *
     * @unauthenticated
     *
     * @return array{data: list<array{month: string, label: string, upcoming: int, past: int}>}
     */
    public function months(): array
    {
        $today = today()->toDateString();

        $rows = Event::published()
            ->selectRaw("DATE_FORMAT(starts_on, '%Y-%m') as ym")
            ->selectRaw('SUM(starts_on >= ?) as upcoming, SUM(starts_on < ?) as past', [$today, $today])
            ->groupBy('ym')
            ->orderByDesc('ym')
            ->toBase()
            ->get();

        return ['data' => $rows->map(fn (object $row) => [
            'month' => (string) $row->ym,
            'label' => Carbon::createFromFormat('!Y-m', (string) $row->ym)->format('F Y'),
            'upcoming' => (int) $row->upcoming,
            'past' => (int) $row->past,
        ])->values()->all()];
    }

    /**
     * One hike.
     *
     * Published hikes, and cancelled ones that were public before (shared links explain the
     * cancellation). Drafts return 404.
     *
     * @unauthenticated
     */
    public function show(Event $event): EventDetailResource
    {
        abort_unless($event->isVisibleOnSite(), 404);

        $event->load(['meetingPoints', 'updates'])->loadSeatCount();

        $albumSlug = $event->isUpcoming() ? null
            : Album::published()->whereBelongsTo($event)->has('photos')->latest('id')->value('slug');

        return (new EventDetailResource($event))->withAlbumSlug($albumSlug);
    }
}
