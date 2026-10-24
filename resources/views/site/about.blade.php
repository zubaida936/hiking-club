@php
    $years = now()->year - $about['founded'];
@endphp

<x-site.layout title="About" description="{{ config('site.about') }}">
    <section class="bg-brand-800 px-4 py-16 text-center text-white sm:px-6">
        <p class="font-display text-sm font-semibold uppercase tracking-[0.2em] text-accent-400">Since {{ $about['founded'] }}</p>
        <h1 class="mt-2 font-display text-4xl font-bold uppercase tracking-wide sm:text-5xl">About {{ config('app.name') }}</h1>
        <p class="mx-auto mt-4 max-w-2xl text-white/80">{{ config('site.about') }}</p>
    </section>

    {{-- Story + numbers --}}
    <section class="mx-auto grid max-w-6xl gap-12 px-4 py-20 sm:px-6 lg:grid-cols-5" aria-labelledby="story-heading">
        <div class="lg:col-span-3">
            <x-site.section-heading eyebrow="Our story" class="text-left!"><span id="story-heading">How it started</span></x-site.section-heading>
            <div class="mt-6 space-y-4 text-lg leading-relaxed text-slate-600">
                @foreach ($about['story'] as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>
        </div>
        <dl class="grid content-center gap-4 sm:grid-cols-3 lg:col-span-2 lg:grid-cols-1">
            <div class="rounded-2xl bg-brand-50 p-6 text-center">
                <dt class="text-sm uppercase tracking-wide text-slate-500">Founded</dt>
                <dd class="font-display text-4xl font-bold text-brand-800">{{ $about['founded'] }}</dd>
            </div>
            @if ($stats['hikes'] > 0)
                <div class="rounded-2xl bg-brand-50 p-6 text-center">
                    <dt class="text-sm uppercase tracking-wide text-slate-500">Hikes organised</dt>
                    <dd class="font-display text-4xl font-bold text-brand-800">{{ number_format($stats['hikes']) }}</dd>
                </div>
            @endif
            @if ($stats['hikers'] > 0)
                <div class="rounded-2xl bg-brand-50 p-6 text-center">
                    <dt class="text-sm uppercase tracking-wide text-slate-500">Hikers on the trail with us</dt>
                    <dd class="font-display text-4xl font-bold text-brand-800">{{ number_format($stats['hikers']) }}</dd>
                </div>
            @elseif ($years > 0)
                <div class="rounded-2xl bg-brand-50 p-6 text-center">
                    <dt class="text-sm uppercase tracking-wide text-slate-500">Years on the trail</dt>
                    <dd class="font-display text-4xl font-bold text-brand-800">{{ $years }}</dd>
                </div>
            @endif
        </dl>
    </section>

    {{-- What we offer --}}
    <section class="bg-slate-50 py-20" aria-labelledby="offer-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <x-site.section-heading eyebrow="Why hike with us"><span id="offer-heading">What We Offer</span></x-site.section-heading>
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ($about['offers'] as [$icon, $title, $text])
                    <div class="rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-slate-200">
                        <p class="text-4xl" aria-hidden="true">{{ $icon }}</p>
                        <h3 class="mt-4 font-display text-lg font-bold uppercase tracking-wide text-brand-900">{{ $title }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Team (CMS → Team) --}}
    @if ($team->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6" aria-labelledby="team-heading">
            <x-site.section-heading eyebrow="The people behind the hikes"><span id="team-heading">Meet the Team</span></x-site.section-heading>
            <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($team as $member)
                    <div class="text-center">
                        @if ($member->photoUrl())
                            <img src="{{ $member->photoUrl() }}" alt="{{ $member->name }}" width="400" height="400" loading="lazy" decoding="async"
                                 class="mx-auto size-40 rounded-full object-cover shadow-md">
                        @else
                            <div class="mx-auto flex size-40 items-center justify-center rounded-full bg-brand-100 font-display text-5xl font-bold text-brand-700" aria-hidden="true">
                                {{ mb_substr($member->name, 0, 1) }}
                            </div>
                        @endif
                        <h3 class="mt-5 font-display text-xl font-bold uppercase tracking-wide text-brand-900">{{ $member->name }}</h3>
                        <p class="font-medium text-accent-600">{{ $member->role }}</p>
                        @if ($member->bio)
                            <p class="mt-2 text-sm text-slate-600">{{ $member->bio }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Testimonials --}}
    <section class="bg-brand-900 py-20 text-white" aria-labelledby="reviews-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <x-site.section-heading eyebrow="What hikers say" :dark="true"><span id="reviews-heading">Reviews</span></x-site.section-heading>
            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ($about['testimonials'] as [$name, $quote])
                    <figure class="rounded-2xl bg-white/5 p-6 ring-1 ring-white/10">
                        <p class="text-accent-400" aria-label="5 out of 5 stars">★★★★★</p>
                        <blockquote class="mt-3 text-white/90">“{{ $quote }}”</blockquote>
                        <figcaption class="mt-4 font-display font-semibold uppercase tracking-wide text-white/70">{{ $name }}</figcaption>
                    </figure>
                @endforeach
            </div>
            @if ($about['google_reviews_url'])
                <p class="mt-10 text-center">
                    <a href="{{ $about['google_reviews_url'] }}" target="_blank" rel="noopener"
                       class="inline-block rounded-full bg-accent-500 px-8 py-3 font-display font-bold uppercase tracking-wide hover:bg-accent-600">Read our Google reviews</a>
                </p>
            @endif
        </div>
    </section>

    {{-- Call to action --}}
    <section class="mx-auto max-w-3xl px-4 py-20 text-center sm:px-6">
        <x-site.section-heading eyebrow="Ready?">Join our next hike</x-site.section-heading>
        <div class="mt-8 flex flex-wrap justify-center gap-4">
            <a href="{{ route('events.index') }}" class="rounded-full bg-accent-500 px-8 py-3 font-display font-bold uppercase tracking-wide text-white hover:bg-accent-600">See upcoming hikes</a>
            <a href="{{ route('contact') }}" class="rounded-full border border-brand-600 px-8 py-3 font-display font-bold uppercase tracking-wide text-brand-700 hover:bg-brand-50">Contact us</a>
        </div>
    </section>
</x-site.layout>
