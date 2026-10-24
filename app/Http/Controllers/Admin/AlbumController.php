<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Content\Actions\SaveAlbum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AlbumRequest;
use App\Models\Album;
use App\Models\Event;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Gallery albums (usually one per hike) and their photos.
 */
class AlbumController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage', Album::class);

        $search = $request->string('q')->trim()->toString();

        return view('admin.albums.index', [
            'albums' => Album::query()
                // Only the cover photo is needed here.
                ->with(['event', 'photos' => fn ($q) => $q->limit(1)])
                ->withCount('photos')
                ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
                ->orderByDesc('taken_on')
                ->orderByDesc('id')
                ->paginate(config('cms.per_page'))
                ->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('manage', Album::class);

        // "New album" from an event prefills it with that hike.
        $event = $request->filled('event') ? Event::find($request->integer('event')) : null;

        return view('admin.albums.create', [
            'album' => new Album([
                'event_id' => $event?->id,
                'title' => $event?->title,
                'taken_on' => $event?->starts_on,
            ]),
            'events' => $this->events(),
        ]);
    }

    public function store(AlbumRequest $request, SaveAlbum $action): RedirectResponse
    {
        Gate::authorize('manage', Album::class);

        $album = $action->handle($request->toData());

        return redirect()->route('admin.albums.edit', $album)->with('success', 'Album created.');
    }

    public function edit(Album $album): View
    {
        Gate::authorize('manage', Album::class);

        return view('admin.albums.edit', ['album' => $album->load('photos'), 'events' => $this->events()]);
    }

    public function update(AlbumRequest $request, Album $album, SaveAlbum $action): RedirectResponse
    {
        Gate::authorize('manage', Album::class);

        $action->handle($request->toData(), $album);

        return redirect()->route('admin.albums.edit', $album)->with('success', 'Album saved.');
    }

    public function destroy(Album $album): RedirectResponse
    {
        Gate::authorize('manage', Album::class);

        $album->delete();

        return redirect()->route('admin.albums.index')->with('success', 'Album and its photos deleted.');
    }

    /**
     * Hikes to link an album to, most recent first.
     *
     * @return Collection<int, Event>
     */
    private function events(): Collection
    {
        return Event::query()->orderByDesc('starts_on')->limit(200)->get(['id', 'title', 'starts_on']);
    }
}
