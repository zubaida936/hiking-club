<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\AlbumPhoto;
use Illuminate\Http\Request;

/**
 * Photo albums (CMS → Gallery). Only published albums with photos; hidden ones give 404
 * (the website's staff preview stays on the website).
 */
class GalleryController extends Controller
{
    public const PER_PAGE = 20;

    /**
     * Albums.
     *
     * Newest first, with the cover photo and the number of photos.
     *
     * @unauthenticated
     *
     * @return array{data: list<array<string, mixed>>, meta: array{current_page: int, last_page: int, total: int}}
     */
    public function index(Request $request): array
    {
        $request->validate(['page' => ['sometimes', 'integer', 'min:1']]);

        $albums = Album::published()
            ->has('photos')
            ->withCount('photos')
            ->with(['photos' => fn ($q) => $q->limit(1)])
            ->orderByDesc('taken_on')->orderByDesc('id')
            ->paginate(self::PER_PAGE);

        return [
            'data' => $albums->getCollection()->map(fn (Album $album) => [
                'slug' => $album->slug,
                'title' => $album->title,
                'taken_on' => $album->taken_on?->toDateString(),
                'cover_url' => $album->coverUrl(),
                'photos_count' => (int) $album->getAttribute('photos_count'),
            ])->values()->all(),
            'meta' => ['current_page' => $albums->currentPage(), 'last_page' => $albums->lastPage(), 'total' => $albums->total()],
        ];
    }

    /**
     * One album.
     *
     * All photos in order. `event_slug` links to the hike when it has a public page.
     *
     * @unauthenticated
     *
     * @return array{data: array<string, mixed>}
     */
    public function show(Album $album): array
    {
        abort_unless($album->is_published, 404);

        $album->load(['photos', 'event']);

        return ['data' => [
            'slug' => $album->slug,
            'title' => $album->title,
            'description' => $album->description,
            'taken_on' => $album->taken_on?->toDateString(),
            'event_slug' => $album->event?->isVisibleOnSite() ? $album->event->slug : null,
            'photos' => $album->photos->map(fn (AlbumPhoto $photo) => ['id' => $photo->id, 'url' => $photo->url()])->values()->all(),
            'web_url' => route('gallery.show', $album),
        ]];
    }
}
