@php
    use Illuminate\Support\Carbon;

    $monthLabel = fn (string $ym) => Carbon::createFromFormat('!Y-m', $ym)->format('F Y');
@endphp

<x-site.layout title="Earlier adventures" description="Every hike we have done, month by month: routes, levels and distances from our past adventures across Lebanon."
               :noindex="$selected !== null || request()->integer('step') > 1">
    <section class="bg-brand-800 px-4 py-16 text-center text-white sm:px-6">
        <a href="{{ route('events.index') }}" class="font-display text-sm font-semibold uppercase tracking-[0.2em] text-accent-400 hover:text-accent-500">Events</a>
        <h1 class="mt-2 font-display text-4xl font-bold uppercase tracking-wide sm:text-5xl">Earlier adventures</h1>
        <p class="mx-auto mt-4 max-w-2xl text-white/80">Every hike we have done, month by month. Pick a month or scroll back in time.</p>
    </section>

    @if ($months === [])
        <section class="mx-auto max-w-xl px-4 py-16 text-center sm:px-6">
            <p class="text-lg text-slate-600">Our first adventures are still ahead of us.</p>
            <a href="{{ route('events.index') }}" class="mt-6 inline-block font-display font-semibold uppercase tracking-wide text-brand-600 hover:text-brand-800">See upcoming hikes &rarr;</a>
        </section>
    @else
        {{-- Month selector: a plain GET form, submitted automatically when the month changes. --}}
        <form method="GET" action="{{ route('events.archive') }}" x-data class="mx-auto flex max-w-7xl flex-wrap items-end justify-center gap-3 px-4 pt-10 sm:px-6" aria-label="Choose a month">
            <div>
                <label for="archive-month" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Month</label>
                <select id="archive-month" name="month" @change="$el.form.requestSubmit()"
                        class="mt-1 rounded-full border border-slate-300 bg-white px-4 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">All months</option>
                    @foreach ($months as $ym => $total)
                        <option value="{{ $ym }}" @selected($selected === $ym)>{{ $monthLabel($ym) }} ({{ $total }})</option>
                    @endforeach
                </select>
            </div>
            <noscript>
                <button type="submit" class="rounded-full bg-brand-600 px-5 py-2 font-display text-sm font-semibold uppercase tracking-wide text-white hover:bg-brand-700">Show</button>
            </noscript>
            @if ($selected)
                <a href="{{ route('events.archive') }}" class="py-2 font-display text-sm font-semibold uppercase tracking-wide text-brand-600 hover:text-brand-800">All months</a>
            @endif
        </form>

        {{-- "Show more months" swaps this whole section for the next step's version (loadMore in resources/js/app.js). --}}
        <section id="archive" x-data="loadMore" class="mx-auto max-w-7xl scroll-mt-24 px-4 py-12 sm:px-6">
            @foreach ($groups as $ym => $events)
                <div id="month-{{ $ym }}" class="scroll-mt-24 {{ $loop->first ? '' : 'mt-14' }}">
                    <h2 class="flex flex-wrap items-baseline gap-3 border-b border-slate-200 pb-3 font-display text-2xl font-bold uppercase tracking-wide text-brand-900">
                        {{ $monthLabel($ym) }}
                        <span class="text-sm font-medium normal-case tracking-normal text-slate-500">{{ $events->count() }} {{ Str::plural('hike', $events->count()) }}</span>
                    </h2>
                    <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($events as $event)
                            <x-site.event-card :event="$event" :past="true" />
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if ($nextStep)
                <div class="mt-12 text-center">
                    <a href="{{ route('events.archive', ['step' => $nextStep]) }}#archive" @click.prevent="load"
                       :aria-busy="loading" :class="loading && 'opacity-60 pointer-events-none'"
                       class="inline-block rounded-full border border-brand-600 px-8 py-3 font-display font-semibold uppercase tracking-wide text-brand-700 hover:bg-brand-100">
                        <span x-text="loading ? 'Loading…' : 'Show more months'">Show more months</span>
                    </a>
                </div>
            @endif
        </section>
    @endif
</x-site.layout>
