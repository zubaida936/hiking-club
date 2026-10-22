@props(['event'])

{{-- Links to the events page until the event detail page exists. --}}
<article class="mx-auto grid max-w-5xl overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-slate-200 md:grid-cols-2">
    <div class="relative min-h-60 bg-linear-to-br from-brand-800 to-brand-500">
        @if ($event->coverImageUrl())
            <img src="{{ $event->coverImageUrl() }}" alt="{{ $event->title }}" width="1200" height="800" loading="lazy" decoding="async"
                 class="absolute inset-0 size-full object-cover">
        @else
            <svg class="absolute inset-x-0 bottom-0 h-2/3 w-full text-black/20" viewBox="0 0 400 200" preserveAspectRatio="none" fill="currentColor" aria-hidden="true">
                <path d="M0 200 90 70l60 60 80-110 90 120 80-60v120z"/>
            </svg>
        @endif
        @if ($event->season_tag)
            <span class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-sm font-medium text-brand-800">{{ $event->season_tag }}</span>
        @endif
    </div>

    <div class="flex flex-col p-6 sm:p-8">
        <p class="font-display text-sm font-semibold uppercase tracking-widest text-accent-600">
            {{ $event->starts_on->format('l, j F Y') }}
        </p>
        <h3 class="mt-2 font-display text-2xl font-bold uppercase tracking-wide text-brand-900">{{ $event->title }}</h3>
        <p class="mt-1 text-slate-500">{{ $event->location }}</p>

        <span class="mt-4 inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $event->difficulty_level->badgeClasses() }}">
            {{ $event->difficulty_level->label() }}
        </span>

        @if ($event->summary)
            <p class="mt-4 text-slate-600">{{ $event->summary }}</p>
        @endif

        <dl class="mt-6 grid grid-cols-3 gap-4 border-y border-slate-100 py-4 text-center">
            <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Distance</dt>
                <dd class="font-display text-lg font-bold text-brand-800">{{ rtrim(rtrim(number_format($event->distance_km, 1), '0'), '.') }} km</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Elevation</dt>
                <dd class="font-display text-lg font-bold text-brand-800">+{{ $event->elevation_gain_m }} m</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Fee</dt>
                <dd class="font-display text-lg font-bold text-brand-800">{{ $event->formattedFee() }}</dd>
            </div>
        </dl>

        <a href="{{ route('events.index') }}"
           class="mt-6 inline-block rounded-full bg-accent-500 px-6 py-3 text-center font-display font-bold uppercase tracking-wide text-white hover:bg-accent-600">
            Reserve Your Spot
        </a>
    </div>
</article>
