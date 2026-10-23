<x-site.layout title="X Events" description="Longer routes. Sustained effort. Prepared hikers. Apply once to join our X Events.">
    <section class="relative isolate overflow-hidden bg-slate-950 px-4 py-24 text-center text-white sm:px-6">
        <p class="font-display text-sm font-semibold uppercase tracking-[0.3em] text-red-500">X Events</p>
        <h1 class="mx-auto mt-3 max-w-3xl font-display text-4xl font-bold uppercase tracking-wide sm:text-6xl">Longer routes. Sustained effort. Prepared hikers.</h1>
        <p class="mx-auto mt-6 max-w-2xl text-white/75">Our most demanding days on the trail: big distances, big climbs and long hours, for hikers ready for them.</p>

        <div class="mt-10">
            @if ($hasAccess)
                <p class="inline-block rounded-full bg-emerald-500/15 px-6 py-3 font-semibold text-emerald-300">✓ You have access to X Events. Pick one below.</p>
            @elseif ($application?->status === \App\Domain\XEvents\Enums\XApplicationStatus::Pending)
                <p class="inline-block rounded-full bg-amber-400/15 px-6 py-3 font-semibold text-amber-300">Your application is under review. We'll contact you on WhatsApp.</p>
            @else
                <a href="{{ route('x-events.apply') }}" class="inline-block rounded-full bg-red-600 px-8 py-3 font-display font-bold uppercase tracking-wide hover:bg-red-700">Apply for X Events</a>
                @guest
                    <p class="mt-4 text-sm text-white/60">Already approved? <a href="{{ route('login') }}" class="font-semibold text-white underline">Log in</a> to book.</p>
                @endguest
            @endif
        </div>
    </section>

    {{-- Requirements --}}
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6" aria-labelledby="requirements-heading">
        <x-site.section-heading eyebrow="What it takes" class="mb-10"><span id="requirements-heading">Requirements</span></x-site.section-heading>
        <div class="grid gap-6 md:grid-cols-3">
            @foreach ([
                ['Endurance', 'You regularly complete long hikes (20 km+, 1,000 m+ of climbing) and keep a steady pace for 8 hours or more.'],
                ['Preparation', 'You train between hikes, know your limits and come with the right gear, water and food for a long day.'],
                ['Team discipline', 'You follow the guides, stay with the group, respect the timings and look after the hikers around you.'],
            ] as [$title, $text])
                <div class="rounded-2xl border border-slate-200 p-6">
                    <h3 class="font-display text-xl font-bold uppercase tracking-wide text-brand-900">{{ $title }}</h3>
                    <p class="mt-2 text-slate-600">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Process --}}
    <section class="bg-slate-50 px-4 py-16 sm:px-6" aria-labelledby="process-heading">
        <div class="mx-auto max-w-6xl">
            <x-site.section-heading eyebrow="How to join" class="mb-10"><span id="process-heading">Three steps</span></x-site.section-heading>
            <ol class="grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['Apply once', 'Tell us about your experience, your hardest hikes and your gear. It takes about 5 minutes.'],
                    ['Manual review', 'Our guides read every application. We may call you on WhatsApp with a few questions.'],
                    ['Personal access', 'Once approved, you get a personal link: log in and book any X event, now and in the future.'],
                ] as $i => [$title, $text])
                    <li class="rounded-2xl bg-white p-6 shadow-sm">
                        <span class="flex size-10 items-center justify-center rounded-full bg-red-600 font-display text-lg font-bold text-white">{{ $i + 1 }}</span>
                        <h3 class="mt-4 font-display text-xl font-bold uppercase tracking-wide text-brand-900">{{ $title }}</h3>
                        <p class="mt-2 text-slate-600">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Upcoming X events --}}
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6" aria-labelledby="xlist-heading">
        <x-site.section-heading eyebrow="Coming up" class="mb-10"><span id="xlist-heading">Upcoming X Events</span></x-site.section-heading>
        @if ($events->isEmpty())
            <p class="rounded-2xl bg-slate-50 p-10 text-center text-slate-600">The next X event is being planned. Apply now so you're ready when it's announced.</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($events as $event)
                    <x-site.event-card :event="$event" />
                @endforeach
            </div>
        @endif
    </section>
</x-site.layout>
