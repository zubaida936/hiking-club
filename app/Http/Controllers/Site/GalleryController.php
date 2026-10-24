<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Album;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Photo albums of past hikes, managed in CMS → Gallery.
 */
class GalleryController extends Controller
{
    private const PER_PAGE = 12;

    public function index(): View
    {
        return view('site.gallery.index', [
            'albums' => Album::published()
                ->has('photos')
                ->withCount('photos')
                ->with(['photos' => fn ($q) => $q->limit(1)])
                ->orderByDesc('taken_on')
                ->orderByDesc('id')
                ->paginate(self::PER_PAGE),
        ]);
    }

    public function show(Request $request, Album $album): View
    {
        // Hidden albums are only visible to staff who manage the gallery (as a preview).
        $preview = ! $album->is_published;
        abort_if($preview && ! $request->user()?->can('manage', Album::class), 404);

        $album->load(['photos', 'event']);

        return view('site.gallery.show', [
            'album' => $album,
            'preview' => $preview,
            // Link back to the hike only when it has a public page.
            'event' => $album->event?->isVisibleOnSite() ? $album->event : null,
        ]);
    }
}
