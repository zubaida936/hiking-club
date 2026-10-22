@php($contact = config('site.contact'))
@php($social = config('site.social'))

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

    {{-- Newsletter --}}
    <section id="newsletter" class="scroll-mt-24 bg-brand-700 px-4 py-16 text-white sm:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <x-site.section-heading eyebrow="Next destination alerts" :dark="true">Never miss a hike</x-site.section-heading>
            <p class="mt-4 text-white/80">Be the first to know when new hikes are announced. Leave your email, your phone number, or both.</p>

            @if (session('newsletter'))
                <p class="mt-8 rounded-2xl bg-white/15 px-5 py-4 font-medium" role="status">{{ session('newsletter') }}</p>
            @else
                <form method="POST" action="{{ route('newsletter.subscribe') }}" class="mt-8 text-left" novalidate>
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
                        <div>
                            <label for="newsletter-email" class="sr-only">Email address</label>
                            <input id="newsletter-email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email"
                                   @error('email') aria-invalid="true" aria-describedby="newsletter-error" @enderror
                                   class="w-full rounded-full border-0 bg-white px-5 py-3 text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-accent-400">
                        </div>
                        <div>
                            <label for="newsletter-phone" class="sr-only">Phone number (optional)</label>
                            <input id="newsletter-phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="+961 70 123 456" autocomplete="tel"
                                   @error('phone') aria-invalid="true" aria-describedby="newsletter-error" @enderror
                                   class="w-full rounded-full border-0 bg-white px-5 py-3 text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-accent-400">
                        </div>
                        <button type="submit" class="rounded-full bg-accent-500 px-6 py-3 font-display font-bold uppercase tracking-wide hover:bg-accent-600">
                            Subscribe
                        </button>
                    </div>
                    {{-- Honeypot: hidden from people, bots fill it in --}}
                    <div class="hidden" aria-hidden="true">
                        <label for="newsletter-website">Website</label>
                        <input id="newsletter-website" name="website" type="text" tabindex="-1" autocomplete="off">
                    </div>
                    @if ($errors->hasAny(['email', 'phone', 'website']))
                        <p id="newsletter-error" class="mt-3 text-center text-sm font-medium text-accent-400" role="alert">
                            {{ $errors->first('email') ?: ($errors->first('phone') ?: 'Something went wrong, please try again.') }}
                        </p>
                    @endif
                    <p class="mt-3 text-center text-sm text-white/70">We'll only use these to tell you about new hikes. Unsubscribe any time.</p>
                </form>
            @endif
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
            <a href="https://instagram.com/{{ $social['instagram'] }}" target="_blank" rel="noopener"
               class="rounded-2xl p-6 text-center ring-1 ring-slate-200 hover:ring-brand-500">
                <p class="font-display font-semibold uppercase tracking-wide text-brand-800">Instagram</p>
                <p class="mt-2 text-slate-600">{{ '@'.$social['instagram'] }}</p>
            </a>
        </div>
        <p class="mt-8 text-center">
            <a href="{{ route('contact') }}" class="font-display font-semibold uppercase tracking-wide text-brand-600 hover:text-brand-800">
                Contact page &rarr;
            </a>
        </p>
    </section>
</x-site.layout>
