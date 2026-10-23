@props(['event', 'past' => false])

{{-- "View Details" appears once the event detail page (route events.show) exists. --}}
<article {{ $attributes->class('flex flex-col overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-slate-200') }}>
    <div class="relative aspect-3/2 bg-linear-to-br from-brand-800 to-brand-500">
        @if ($event->coverImageUrl())
            <img src="{{ $event->coverImageUrl() }}" alt="{{ $event->title }}" width="1200" height="800" loading="lazy" decoding="async"
                 @class(['absolute inset-0 size-full object-cover', 'grayscale-[40%]' => $past])>
        @else
            <svg class="absolute inset-x-0 bottom-0 h-2/3 w-full text-black/20" viewBox="0 0 400 200" preserveAspectRatio="none" fill="currentColor" aria-hidden="true">
                <path d="M0 200 90 70l60 60 80-110 90 120 80-60v120z"/>
            </svg>
        @endif
        <div class="absolute inset-x-3 top-3 flex flex-wrap gap-2">
            @if ($event->season_tag)
                <span class="rounded-full bg-white/90 px-3 py-1 text-xs font-medium text-brand-800">{{ $event->season_tag }}</span>
            @endif
            @if ($event->is_xtreme)
                <span class="rounded-full bg-red-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">X Event</span>
            @endif
        </div>
    </div>

    <div class="flex flex-1 flex-col p-5">
        <p class="font-display text-sm font-semibold uppercase tracking-widest text-accent-600">
            <time datetime="{{ $event->starts_on->toDateString() }}">{{ $event->starts_on->format('D, j M Y') }}</time>
        </p>
        <h3 class="mt-1 font-display text-xl font-bold uppercase tracking-wide text-brand-900">{{ $event->title }}</h3>
        <p class="text-sm text-slate-500">{{ $event->location }}</p>

        <span class="mt-3 inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $event->difficulty_level->badgeClasses() }}">
            {{ $event->difficulty_level->label() }}
        </span>

        <dl class="mt-4 grid grid-cols-3 gap-2 border-t border-slate-100 pt-4 text-center">
            <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Distance</dt>
                <dd class="font-display font-bold text-brand-800">{{ $event->formattedDistance() }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Effort</dt>
                <dd class="font-display font-bold text-brand-800">{{ $event->effort_score }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Fee</dt>
                <dd class="font-display font-bold text-brand-800">{{ $event->formattedFee() }}</dd>
            </div>
        </dl>

        @if (Route::has('events.show'))
            <a href="{{ route('events.show', $event) }}"
               @class([
                   'mt-5 block rounded-full px-5 py-2.5 text-center font-display text-sm font-bold uppercase tracking-wide',
                   'bg-accent-500 text-white hover:bg-accent-600' => ! $past,
                   'border border-brand-600 text-brand-700 hover:bg-brand-50' => $past,
               ])>
                View Details<span class="sr-only">: {{ $event->title }}</span>
            </a>
        @endif
    </div>
</article>
