<x-site.layout title="Events" description="Upcoming guided hikes across Lebanon for every level, plus a look back at our past adventures. Filter by difficulty level or month.">
    <section class="bg-brand-800 px-4 py-16 text-center text-white sm:px-6">
        <p class="font-display text-sm font-semibold uppercase tracking-[0.2em] text-accent-400">Hike with us</p>
        <h1 class="mt-2 font-display text-4xl font-bold uppercase tracking-wide sm:text-5xl">Events</h1>
        <p class="mx-auto mt-4 max-w-2xl text-white/80">Pick your next trail. Every hike shows its distance, effort score and difficulty level so you can choose one that fits.</p>
    </section>

    {{-- Filters: a plain GET form, submitted automatically when a select changes. --}}
    <form method="GET" action="{{ route('events.index') }}#upcoming" x-data
          class="mx-auto flex max-w-7xl flex-wrap items-end justify-center gap-3 px-4 pt-10 sm:px-6" aria-label="Filter events">
        <div>
            <label for="filter-level" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Level</label>
            <select id="filter-level" name="level" @change="$el.form.requestSubmit()"
                    class="mt-1 rounded-full border border-slate-300 bg-white px-4 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">All levels</option>
                @foreach ($levels as $option)
                    <option value="{{ $option->value }}" @selected($level === $option)>{{ $option->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-month" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Month</label>
            <select id="filter-month" name="month" @change="$el.form.requestSubmit()"
                    class="mt-1 rounded-full border border-slate-300 bg-white px-4 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">Any month</option>
                @foreach ($months as $value => $label)
                    <option value="{{ $value }}" @selected($month === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <noscript>
            <button type="submit" class="rounded-full bg-brand-600 px-5 py-2 font-display text-sm font-semibold uppercase tracking-wide text-white hover:bg-brand-700">Filter</button>
        </noscript>
        @if ($level || $month)
            <a href="{{ route('events.index') }}" class="py-2 font-display text-sm font-semibold uppercase tracking-wide text-brand-600 hover:text-brand-800">Clear filters</a>
        @endif
    </form>

    {{-- Upcoming --}}
    <section id="upcoming" class="mx-auto max-w-7xl scroll-mt-24 px-4 py-14 sm:px-6">
        <x-site.section-heading eyebrow="Reserve your spot" class="mb-10">Upcoming Events</x-site.section-heading>

        @if ($upcoming->isNotEmpty())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($upcoming as $event)
                    <x-site.event-card :event="$event" />
                @endforeach
            </div>
        @else
            <div class="mx-auto max-w-xl rounded-2xl bg-brand-50 p-10 text-center">
                @if ($level || $month)
                    <p class="text-lg text-slate-600">No upcoming hikes match these filters.</p>
                    <a href="{{ route('events.index') }}" class="mt-6 inline-block font-display font-semibold uppercase tracking-wide text-brand-600 hover:text-brand-800">Show all events &rarr;</a>
                @else
                    <p class="text-lg text-slate-600">Our next adventure is being planned. Check back soon!</p>
                    <a href="{{ route('home') }}#newsletter" class="mt-6 inline-block font-display font-semibold uppercase tracking-wide text-brand-600 hover:text-brand-800">Get an alert when it's announced &rarr;</a>
                @endif
            </div>
        @endif
    </section>

    {{-- Past: "Load More" swaps this whole section for the next page's version (see resources/js/app.js). --}}
    @if ($past->isNotEmpty())
        <section id="past" x-data="loadMore" class="scroll-mt-24 bg-slate-50 px-4 py-14 sm:px-6">
            <div class="mx-auto max-w-7xl">
                <x-site.section-heading eyebrow="Where we've been" class="mb-10">Past Events</x-site.section-heading>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($past as $event)
                        <x-site.event-card :event="$event" :past="true" />
                    @endforeach
                </div>
                @if ($nextPage)
                    <div class="mt-10 text-center">
                        <a href="{{ request()->fullUrlWithQuery(['page' => $nextPage]) }}#past" @click.prevent="load"
                           :aria-busy="loading" :class="loading && 'opacity-60 pointer-events-none'"
                           class="inline-block rounded-full border border-brand-600 px-8 py-3 font-display font-semibold uppercase tracking-wide text-brand-700 hover:bg-brand-100">
                            <span x-text="loading ? 'Loading…' : 'Load More'">Load More</span>
                        </a>
                    </div>
                @endif
            </div>
        </section>
    @endif
</x-site.layout>
