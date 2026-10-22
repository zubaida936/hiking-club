@php($contact = config('site.contact'))

<x-site.layout>
    <h1 class="sr-only">{{ config('app.name') }}</h1>
    <x-site.hero-carousel :slides="config('site.hero')" />

    {{-- About --}}
    <section id="about" class="mx-auto max-w-3xl px-4 py-20 text-center sm:px-6">
        <x-site.section-heading eyebrow="Who we are">About {{ config('app.name') }}</x-site.section-heading>
        <p class="mt-6 text-lg leading-relaxed text-slate-600">{{ config('site.about') }}</p>
        <a href="{{ route('about') }}" class="mt-8 inline-block font-display font-semibold uppercase tracking-wide text-brand-600 hover:text-brand-800">
            Learn more about us &rarr;
        </a>
    </section>

    {{-- Next challenge --}}
    <section id="next-challenge" class="bg-brand-50 px-4 py-20 sm:px-6">
        <x-site.section-heading eyebrow="Don't miss it" class="mb-10">Next Challenge</x-site.section-heading>
        @if ($nextEvent)
            <x-site.next-event-card :event="$nextEvent" />
        @else
            <div class="mx-auto max-w-xl rounded-2xl bg-white p-10 text-center shadow ring-1 ring-slate-200">
                <p class="text-lg text-slate-600">Our next adventure is being planned. Check back soon!</p>
                <a href="{{ route('events.index') }}" class="mt-6 inline-block font-display font-semibold uppercase tracking-wide text-brand-600 hover:text-brand-800">
                    Browse events &rarr;
                </a>
            </div>
        @endif
    </section>

    {{-- Leaderboard (placeholder until the points engine exists) --}}
    <section id="leaderboard" class="mx-auto max-w-3xl px-4 py-20 sm:px-6">
        <x-site.section-heading eyebrow="Rank system">Top 10 Hikers</x-site.section-heading>
        <div class="mt-10 overflow-hidden rounded-2xl ring-1 ring-slate-200">
            <ol class="divide-y divide-slate-100" aria-hidden="true">
                @foreach (range(1, 5) as $rank)
                    <li class="flex items-center gap-4 px-6 py-4">
                        <span class="w-6 font-display font-bold text-brand-700">{{ $rank }}</span>
                        <span class="h-3 flex-1 rounded bg-slate-100"></span>
                        <span class="h-3 w-16 rounded bg-slate-100"></span>
                    </li>
                @endforeach
            </ol>
            <p class="bg-slate-50 px-6 py-4 text-center text-slate-600">
                The leaderboard goes live once members start earning points on hikes.
            </p>
        </div>
        <div class="mt-8 flex flex-wrap justify-center gap-4">
            <a href="{{ route('account') }}" class="rounded-full bg-brand-600 px-6 py-3 font-display font-semibold uppercase tracking-wide text-white hover:bg-brand-700">
                View My Progress
            </a>
            <a href="{{ route('rank-system') }}" class="rounded-full border border-brand-600 px-6 py-3 font-display font-semibold uppercase tracking-wide text-brand-700 hover:bg-brand-50">
                How ranks work
            </a>
        </div>
    </section>

    {{-- X Events promo --}}
    <section id="x-events" class="relative isolate overflow-hidden bg-slate-950 px-4 py-24 text-white sm:px-6">
        <div class="absolute inset-0 -z-10 bg-linear-to-r from-slate-950 via-slate-900 to-red-900/70"></div>
        <div class="mx-auto max-w-4xl text-center">
            <x-site.section-heading eyebrow="For experienced hikers" :dark="true">X Events</x-site.section-heading>
            <p class="mx-auto mt-6 max-w-2xl text-lg text-white/80">
                Long distances, big elevation and multi-day expeditions. X Events are invitation-only:
                apply, tell us about your experience, and our team reviews every application.
            </p>
            <a href="{{ route('x-events') }}" class="mt-10 inline-block rounded-full bg-accent-500 px-8 py-3 font-display font-bold uppercase tracking-wide hover:bg-accent-600">
                Discover X Events
            </a>
        </div>
    </section>

    {{-- Newsletter (placeholder until the newsletter signup task) --}}
    <section id="newsletter" class="bg-brand-700 px-4 py-16 text-white sm:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <x-site.section-heading eyebrow="Next destination alerts" :dark="true">Never miss a hike</x-site.section-heading>
            <p class="mt-4 text-white/80">Get an email as soon as new hikes are announced.</p>
            <form class="mt-8 flex flex-col gap-3 sm:flex-row" onsubmit="return false" aria-describedby="newsletter-soon">
                <label for="newsletter-email" class="sr-only">Email address</label>
                <input id="newsletter-email" type="email" placeholder="you@example.com" disabled
                       class="flex-1 rounded-full border-0 bg-white/90 px-5 py-3 text-slate-800 placeholder:text-slate-400 disabled:cursor-not-allowed">
                <button type="submit" disabled class="rounded-full bg-accent-500 px-6 py-3 font-display font-bold uppercase tracking-wide opacity-60 disabled:cursor-not-allowed">
                    Subscribe
                </button>
            </form>
            <p id="newsletter-soon" class="mt-3 text-sm text-white/70">Sign-up opens soon.</p>
        </div>
    </section>

    {{-- Contact --}}
    <section id="contact" class="mx-auto max-w-5xl px-4 py-20 sm:px-6">
        <x-site.section-heading eyebrow="Questions?">Get in touch</x-site.section-heading>
        <div class="mt-10 grid gap-6 sm:grid-cols-3">
            <a href="https://wa.me/{{ $contact['whatsapp'] }}" target="_blank" rel="noopener"
               class="rounded-2xl p-6 text-center ring-1 ring-slate-200 hover:ring-brand-500">
                <p class="font-display font-semibold uppercase tracking-wide text-brand-800">WhatsApp</p>
                <p class="mt-2 text-slate-600">{{ $contact['phone'] }}</p>
            </a>
            <a href="mailto:{{ $contact['email'] }}" class="rounded-2xl p-6 text-center ring-1 ring-slate-200 hover:ring-brand-500">
                <p class="font-display font-semibold uppercase tracking-wide text-brand-800">Email</p>
                <p class="mt-2 break-all text-slate-600">{{ $contact['email'] }}</p>
            </a>
            <a href="https://instagram.com/{{ $contact['instagram'] }}" target="_blank" rel="noopener"
               class="rounded-2xl p-6 text-center ring-1 ring-slate-200 hover:ring-brand-500">
                <p class="font-display font-semibold uppercase tracking-wide text-brand-800">Instagram</p>
                <p class="mt-2 text-slate-600">{{ '@'.$contact['instagram'] }}</p>
            </a>
        </div>
        <p class="mt-8 text-center">
            <a href="{{ route('contact') }}" class="font-display font-semibold uppercase tracking-wide text-brand-600 hover:text-brand-800">
                Contact page &rarr;
            </a>
        </p>
    </section>
</x-site.layout>
