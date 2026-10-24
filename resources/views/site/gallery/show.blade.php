@php
    $urls = $album->photos->map(fn ($photo) => $photo->url())->values();
@endphp

<x-site.layout :title="$album->title" :description="$album->description ?: 'Photos from '.$album->title.' with '.config('app.name').'.'"
               :image="$album->coverUrl()" :noindex="$preview">
    @if ($preview)
        <div class="bg-amber-100 px-4 py-3 text-center text-sm font-medium text-amber-900">Preview: this album is hidden and not visible to visitors yet.</div>
    @endif

    <section class="bg-brand-800 px-4 py-14 text-center text-white sm:px-6">
        <a href="{{ route('gallery.index') }}" class="font-display text-sm font-semibold uppercase tracking-[0.2em] text-accent-400 hover:text-accent-500">Photo Gallery</a>
        <h1 class="mt-2 font-display text-4xl font-bold uppercase tracking-wide sm:text-5xl">{{ $album->title }}</h1>
        <p class="mt-3 text-white/80">
            @if ($album->taken_on)<time datetime="{{ $album->taken_on->toDateString() }}">{{ $album->taken_on->format('j F Y') }}</time> · @endif
            {{ $album->photos->count() }} {{ Str::plural('photo', $album->photos->count()) }}
        </p>
        @if ($album->description)
            <p class="mx-auto mt-4 max-w-2xl whitespace-pre-line text-white/80">{{ $album->description }}</p>
        @endif
        @if ($event)
            <a href="{{ route('events.show', $event) }}" class="mt-6 inline-block font-display text-sm font-semibold uppercase tracking-wide text-white underline underline-offset-4 hover:text-accent-400">About this hike &rarr;</a>
        @endif
    </section>

    {{-- Photo grid + lightbox: arrows / swipe to move, Esc to close. --}}
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6"
             x-data="{
                 photos: @js($urls),
                 open: null,
                 touchX: null,
                 show(i) { this.open = (i + this.photos.length) % this.photos.length },
                 close() { this.open = null },
             }"
             @keydown.escape.window="close()"
             @keydown.arrow-right.window="open !== null && show(open + 1)"
             @keydown.arrow-left.window="open !== null && show(open - 1)">
        @if ($album->photos->isNotEmpty())
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 sm:gap-3 lg:grid-cols-4">
                @foreach ($album->photos as $i => $photo)
                    <button type="button" @click="show({{ $i }})" class="group relative block aspect-square overflow-hidden rounded-lg bg-slate-200"
                            aria-label="Open photo {{ $i + 1 }} of {{ $album->photos->count() }}">
                        <img src="{{ $photo->url() }}" alt="" loading="lazy" decoding="async" class="size-full object-cover transition duration-300 group-hover:scale-105">
                    </button>
                @endforeach
            </div>
        @else
            <p class="text-center text-slate-600">No photos in this album yet.</p>
        @endif

        <div x-show="open !== null" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-label="Photo viewer"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/95"
             @click.self="close()"
             @touchstart="touchX = $event.changedTouches[0].clientX"
             @touchend="if (touchX !== null) { const dx = $event.changedTouches[0].clientX - touchX; if (Math.abs(dx) > 50) show(open + (dx < 0 ? 1 : -1)); touchX = null }">
            <template x-if="open !== null">
                <img :src="photos[open]" alt="" class="max-h-[90vh] max-w-[95vw] object-contain">
            </template>
            <p class="absolute left-4 top-4 text-sm text-white/70" x-text="open !== null ? `${open + 1} / ${photos.length}` : ''"></p>
            <button type="button" @click="close()" class="absolute right-3 top-3 rounded-full p-2 text-white hover:bg-white/10" aria-label="Close">
                <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
            <template x-if="photos.length > 1">
                <div>
                    <button type="button" @click="show(open - 1)" class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white hover:bg-white/20" aria-label="Previous photo">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                    </button>
                    <button type="button" @click="show(open + 1)" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-white/10 p-3 text-white hover:bg-white/20" aria-label="Next photo">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    </button>
                </div>
            </template>
        </div>
    </section>
</x-site.layout>
