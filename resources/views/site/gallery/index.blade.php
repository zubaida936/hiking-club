<x-site.layout title="Photo Gallery" description="Photos from our hikes across Lebanon: summits, valleys, cedar forests and the people who walk them with us."
               :noindex="$albums->currentPage() > 1">
    <section class="bg-brand-800 px-4 py-16 text-center text-white sm:px-6">
        <p class="font-display text-sm font-semibold uppercase tracking-[0.2em] text-accent-400">On the trail</p>
        <h1 class="mt-2 font-display text-4xl font-bold uppercase tracking-wide sm:text-5xl">Photo Gallery</h1>
        <p class="mx-auto mt-4 max-w-2xl text-white/80">Moments from our hikes. Find yourself in the pictures, or see what is waiting for you.</p>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6">
        @if ($albums->isNotEmpty())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($albums as $album)
                    <a href="{{ route('gallery.show', $album->slug) }}" class="group block overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-slate-200">
                        <div class="relative aspect-3/2 overflow-hidden bg-brand-800">
                            <img src="{{ $album->coverUrl() }}" alt="" width="1200" height="800" loading="lazy" decoding="async"
                                 class="size-full object-cover transition duration-500 group-hover:scale-105">
                            <span class="absolute bottom-3 right-3 rounded-full bg-black/60 px-3 py-1 text-xs font-medium text-white">
                                {{ $album->photos_count }} {{ Str::plural('photo', $album->photos_count) }}
                            </span>
                        </div>
                        <div class="p-5">
                            @if ($album->taken_on)
                                <p class="text-sm text-slate-500"><time datetime="{{ $album->taken_on->toDateString() }}">{{ $album->taken_on->format('j M Y') }}</time></p>
                            @endif
                            <h2 class="mt-1 font-display text-xl font-bold uppercase tracking-wide text-brand-900 group-hover:text-brand-600">{{ $album->title }}</h2>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-10">{{ $albums->links() }}</div>
        @else
            <div class="mx-auto max-w-xl rounded-2xl bg-brand-50 p-10 text-center">
                <p class="text-lg text-slate-600">Photos from our hikes are coming soon.</p>
                <a href="{{ route('events.index') }}" class="mt-6 inline-block font-display font-semibold uppercase tracking-wide text-brand-600 hover:text-brand-800">See upcoming hikes &rarr;</a>
            </div>
        @endif
    </section>
</x-site.layout>
