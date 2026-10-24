<x-admin.layout title="Gallery">
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <form method="GET" class="flex flex-wrap items-end gap-2">
            <input name="q" value="{{ request('q') }}" placeholder="Search albums" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <x-admin.button variant="secondary">Search</x-admin.button>
        </form>
        <div class="ml-auto">
            <x-admin.button :href="route('admin.albums.create')">+ New album</x-admin.button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($albums as $album)
            <a href="{{ route('admin.albums.edit', $album) }}" class="block overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200 hover:ring-emerald-500">
                @if ($album->coverUrl())
                    <img src="{{ $album->coverUrl() }}" alt="" class="aspect-3/2 w-full object-cover">
                @else
                    <div class="flex aspect-3/2 w-full items-center justify-center bg-slate-100 text-xs text-slate-400">No photos yet</div>
                @endif
                <div class="space-y-1 p-3">
                    <p class="font-medium">{{ $album->title }}</p>
                    <p class="text-xs text-slate-500">
                        {{ $album->photos_count }} {{ Str::plural('photo', $album->photos_count) }}
                        @if ($album->taken_on) · {{ $album->taken_on->format('j M Y') }} @endif
                    </p>
                    @if ($album->event)<p class="truncate text-xs text-slate-500">Hike: {{ $album->event->title }}</p>@endif
                    <x-admin.badge :classes="$album->is_published ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700'">{{ $album->is_published ? 'Published' : 'Hidden' }}</x-admin.badge>
                </div>
            </a>
        @empty
            <p class="col-span-full rounded-lg bg-white p-8 text-center text-slate-500 shadow-sm">No albums yet.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $albums->links() }}</div>
</x-admin.layout>
