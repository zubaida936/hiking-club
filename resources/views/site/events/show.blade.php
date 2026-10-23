@php
    $upcoming = $event->isUpcoming() && ! $event->isCancelled();
    $whatsappUrl = \App\Support\WhatsApp::link($whatsappMessage);
    $steps = $event->itinerarySteps();

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Event',
        'name' => $event->title,
        'description' => $event->summary ?: Str::limit((string) $event->description, 300),
        'startDate' => $event->starts_on->toDateString(),
        'eventStatus' => 'https://schema.org/'.($event->isCancelled() ? 'EventCancelled' : 'EventScheduled'),
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'location' => ['@type' => 'Place', 'name' => $event->location, 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $event->location, 'addressCountry' => 'LB']],
        'image' => [$event->coverImageUrl() ?: asset(config('site.share_image'))],
        'organizer' => ['@type' => 'Organization', 'name' => config('app.name'), 'url' => route('home')],
        'offers' => [
            '@type' => 'Offer',
            'price' => $event->feeAmount(),
            'priceCurrency' => 'USD',
            'availability' => 'https://schema.org/'.($upcoming && $event->seatsLeft() > 0 ? 'InStock' : 'SoldOut'),
            'url' => route('events.show', $event),
        ],
    ];
@endphp

<x-site.layout :title="$event->title" :description="$event->summary ?: Str::limit((string) $event->description, 160)"
               :image="$event->coverImageUrl()" :whatsapp="$whatsappMessage">
    @push('head')
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endpush

    {{-- Hero --}}
    <section class="relative isolate overflow-hidden bg-linear-to-br from-brand-900 to-brand-600 text-white">
        @if ($event->coverImageUrl())
            <img src="{{ $event->coverImageUrl() }}" alt="" width="1920" height="1080" fetchpriority="high" decoding="async"
                 @class(['absolute inset-0 -z-10 size-full object-cover', 'grayscale-[40%]' => ! $upcoming])>
            <div class="absolute inset-0 -z-10 bg-linear-to-t from-black/80 via-black/40 to-black/20"></div>
        @else
            <svg class="absolute inset-x-0 bottom-0 -z-10 h-2/3 w-full text-black/20" viewBox="0 0 400 200" preserveAspectRatio="none" fill="currentColor" aria-hidden="true">
                <path d="M0 200 90 70l60 60 80-110 90 120 80-60v120z"/>
            </svg>
        @endif

        <div class="mx-auto max-w-7xl px-4 pb-12 pt-8 sm:px-6 sm:pb-16">
            <a href="{{ route('events.index') }}" class="inline-flex items-center gap-1 font-display text-sm font-semibold uppercase tracking-wide text-white/80 hover:text-white">
                &larr; Back to events
            </a>

            <div class="mt-16 max-w-3xl sm:mt-24">
                <div class="flex flex-wrap gap-2">
                    @if ($event->season_tag)
                        <span class="rounded-full bg-white/90 px-3 py-1 text-xs font-medium text-brand-800">{{ $event->season_tag }}</span>
                    @endif
                    @if ($event->is_xtreme)
                        <span class="rounded-full bg-red-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">X Event</span>
                    @endif
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $event->difficulty_level->badgeClasses() }}">{{ $event->difficulty_level->label() }}</span>
                </div>
                <p class="mt-4 font-display text-sm font-semibold uppercase tracking-widest text-accent-400">
                    <time datetime="{{ $event->starts_on->toDateString() }}">{{ $event->starts_on->format('l, j F Y') }}</time>
                </p>
                <h1 class="mt-2 font-display text-4xl font-bold uppercase tracking-wide sm:text-5xl">{{ $event->title }}</h1>
                <p class="mt-2 text-lg text-white/85">{{ $event->location }}</p>
            </div>

            @if ($event->photo_credit && $event->coverImageUrl())
                <p class="mt-8 text-right text-xs text-white/70">Photo: {{ $event->photo_credit }}</p>
            @endif
        </div>
    </section>

    {{-- Status notices --}}
    @if ($event->isCancelled())
        <div class="bg-red-50 px-4 py-4 text-red-800 sm:px-6" role="status">
            <p class="mx-auto max-w-7xl"><strong>This hike has been cancelled.</strong> {{ $event->cancellation_reason }}
                <a href="{{ route('events.index') }}" class="font-semibold underline">See other upcoming hikes</a></p>
        </div>
    @elseif (! $upcoming)
        <div class="bg-slate-100 px-4 py-4 text-slate-700 sm:px-6" role="status">
            <p class="mx-auto max-w-7xl"><strong>This hike has already taken place.</strong>
                <a href="{{ route('events.index') }}" class="font-semibold text-brand-700 underline">See upcoming hikes</a></p>
        </div>
    @endif

    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-3 lg:py-16">
        {{-- Main column --}}
        <div class="space-y-12 lg:col-span-2">
            {{-- Key numbers --}}
            <dl class="grid grid-cols-2 gap-4 rounded-2xl bg-brand-50 p-6 text-center sm:grid-cols-4">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Distance</dt>
                    <dd class="font-display text-2xl font-bold text-brand-800">{{ $event->formattedDistance() }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Elevation</dt>
                    <dd class="font-display text-2xl font-bold text-brand-800">+{{ $event->elevation_gain_m }} / -{{ $event->elevation_loss_m }} m</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Effort score</dt>
                    <dd class="font-display text-2xl font-bold text-brand-800">{{ $event->effort_score }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Duration</dt>
                    <dd class="font-display text-2xl font-bold text-brand-800">{{ $event->formattedDuration() ?? '—' }}</dd>
                </div>
            </dl>

            {{-- Level --}}
            <section aria-labelledby="level-heading" class="flex gap-4 rounded-2xl border border-slate-200 p-6">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-full font-display text-xl font-bold {{ $event->difficulty_level->badgeClasses() }}">{{ $event->difficulty_level->value }}</span>
                <div>
                    <h2 id="level-heading" class="font-display text-lg font-bold uppercase tracking-wide text-brand-900">{{ $event->difficulty_level->label() }}</h2>
                    <p class="mt-1 text-slate-600">{{ $event->difficulty_level->description() }}</p>
                </div>
            </section>

            @if ($event->summary || $event->description)
                <section aria-labelledby="about-heading">
                    <h2 id="about-heading" class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">About this hike</h2>
                    @if ($event->summary)
                        <p class="mt-4 text-lg font-medium text-slate-700">{{ $event->summary }}</p>
                    @endif
                    @foreach (preg_split('/\R{2,}/', trim((string) $event->description)) ?: [] as $paragraph)
                        @if (trim($paragraph) !== '')
                            <p class="mt-4 leading-relaxed text-slate-700">{!! nl2br(e(trim($paragraph))) !!}</p>
                        @endif
                    @endforeach
                </section>
            @endif

            @if ($steps !== [])
                <section aria-labelledby="itinerary-heading">
                    <h2 id="itinerary-heading" class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">Itinerary</h2>
                    <ol class="mt-6 border-l-2 border-brand-100">
                        @foreach ($steps as $step)
                            <li class="relative pb-6 pl-6 last:pb-0">
                                <span class="absolute -left-[7px] top-1.5 size-3 rounded-full bg-accent-500" aria-hidden="true"></span>
                                @if ($step['time'])
                                    <time class="block font-display text-sm font-bold text-accent-600">{{ $step['time'] }}</time>
                                @endif
                                <p class="text-slate-700">{{ $step['text'] }}</p>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            @if ($event->meetingPoints->isNotEmpty())
                <section aria-labelledby="meeting-heading">
                    <h2 id="meeting-heading" class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">Meeting points</h2>
                    <p class="mt-1 text-sm text-slate-500">Pickup times are Lebanon time. Choose the stop closest to you.</p>
                    <ul class="mt-4 divide-y divide-slate-100 rounded-2xl border border-slate-200">
                        @foreach ($event->meetingPoints as $point)
                            <li class="flex items-center justify-between gap-4 px-5 py-3">
                                <span class="text-slate-700">{{ $point->name }}</span>
                                <time class="font-display font-bold text-brand-800">{{ $point->pickupTimeShort() }}</time>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($event->included || $event->what_to_bring)
                <div class="grid gap-8 sm:grid-cols-2">
                    @if ($event->included)
                        <section aria-labelledby="included-heading">
                            <h2 id="included-heading" class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">What's included</h2>
                            <ul class="mt-4 space-y-2">
                                @foreach ($event->included as $item)
                                    <li class="flex gap-2 text-slate-700"><span class="text-brand-600" aria-hidden="true">✓</span> {{ $item }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                    @if ($event->what_to_bring)
                        <section aria-labelledby="bring-heading">
                            <h2 id="bring-heading" class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">What to bring</h2>
                            <ul class="mt-4 space-y-2">
                                @foreach ($event->what_to_bring as $item)
                                    <li class="flex gap-2 text-slate-700"><span class="text-accent-600" aria-hidden="true">•</span> {{ $item }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </div>
            @endif

            {{-- Technical details --}}
            <section aria-labelledby="technical-heading">
                <h2 id="technical-heading" class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">Technical details</h2>
                <dl class="mt-4 divide-y divide-slate-100 rounded-2xl border border-slate-200">
                    @foreach (array_filter([
                        'Distance' => $event->formattedDistance(),
                        'Elevation gain' => '+'.$event->elevation_gain_m.' m',
                        'Elevation loss' => '-'.$event->elevation_loss_m.' m',
                        'Estimated duration' => $event->formattedDuration(),
                        'Effort score' => (string) $event->effort_score,
                        'Difficulty level' => $event->difficulty_level->label(),
                        'Start area' => $event->location,
                    ]) as $label => $value)
                        <div class="flex justify-between gap-4 px-5 py-3">
                            <dt class="text-slate-500">{{ $label }}</dt>
                            <dd class="text-right font-semibold text-slate-800">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
                <p class="mt-2 text-xs text-slate-500">The effort score combines distance and climbing: every {{ config('difficulty.gain_per_km') }} m of ascent counts like an extra kilometre.</p>
            </section>

            @if ($event->route_points)
                <section aria-labelledby="map-heading">
                    <div class="flex flex-wrap items-end justify-between gap-2">
                        <h2 id="map-heading" class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">Route map</h2>
                        @if ($event->gpxUrl())
                            <a href="{{ $event->gpxUrl() }}" download="{{ $event->slug }}.gpx"
                               class="font-display text-sm font-semibold uppercase tracking-wide text-brand-600 hover:text-brand-800">Download GPX &darr;</a>
                        @endif
                    </div>
                    <div x-data="routeMap(@js($event->route_points))" class="relative z-0 mt-4 h-80 overflow-hidden rounded-2xl bg-slate-100 sm:h-96"
                         role="img" aria-label="Map of the {{ $event->title }} route">
                        <p class="flex h-full items-center justify-center text-sm text-slate-500" x-show="!ready">Loading map…</p>
                    </div>
                </section>
            @endif

            <a href="{{ route('events.index') }}" class="inline-block font-display font-semibold uppercase tracking-wide text-brand-600 hover:text-brand-800">
                &larr; Back to events
            </a>
        </div>

        {{-- Side column: price, seats and inquiry --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded-2xl bg-white p-6 shadow-lg ring-1 ring-slate-200">
                <p class="text-xs uppercase tracking-wide text-slate-500">Fee per person</p>
                <p class="font-display text-4xl font-bold text-brand-900">{{ $event->formattedFee() }}</p>

                <dl class="mt-6 space-y-3 border-t border-slate-100 pt-6 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Date</dt>
                        <dd class="text-right font-semibold">{{ $event->starts_on->format('D, j M Y') }}</dd>
                    </div>
                    @if ($event->meetingPoints->isNotEmpty())
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">First pickup</dt>
                            <dd class="text-right font-semibold">{{ $event->meetingPoints->first()->pickupTimeShort() }} · {{ $event->meetingPoints->first()->name }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Level</dt>
                        <dd class="text-right font-semibold">{{ $event->difficulty_level->label() }}</dd>
                    </div>
                    @if ($upcoming)
                        @php($points = app(\App\Domain\Loyalty\PointsRules::class))
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Points</dt>
                            <dd class="text-right font-semibold text-accent-600">
                                +{{ $points->hikePoints($event) }}
                                @if ($points->earlyBookingOpen($event))
                                    <span class="block text-xs font-normal text-slate-500">+{{ config('points.early_booking.points') }} if you book now ({{ config('points.early_booking.days') }}+ days ahead)</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                    @if ($upcoming)
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Seats left</dt>
                            <dd @class(['text-right font-semibold', 'text-red-600' => $event->seatsLeft() <= 5])>
                                {{ $event->seatsLeft() > 0 ? $event->seatsLeft().' of '.$event->capacity : 'Fully booked' }}
                            </dd>
                        </div>
                    @endif
                </dl>

                @if ($event->is_xtreme && $upcoming)
                    <p class="mt-6 rounded-lg bg-red-50 p-3 text-sm text-red-800">X Event: only approved hikers can register.</p>
                @endif

                @if ($event->isOpenForRegistration() && ! $event->isSoldOut())
                    <a href="{{ route('events.register', $event) }}"
                       class="mt-6 block rounded-full bg-accent-500 px-6 py-3 text-center font-display font-bold uppercase tracking-wide text-white hover:bg-accent-600">
                        {{ $event->registerLabel() }}
                    </a>
                    <p class="mt-2 text-center text-xs text-slate-500">Bookings close {{ $event->registrationDeadline()->setTimezone(config('site.timezone'))->format('D j M \a\t H:i') }} (Lebanon time).</p>
                @elseif ($event->acceptsWaitlist())
                    <p class="mt-6 text-center font-display font-bold uppercase tracking-wide text-red-600">Fully booked</p>
                    <a href="{{ route('events.register', $event) }}"
                       class="mt-2 block rounded-full border-2 border-accent-500 px-6 py-2.5 text-center font-display font-bold uppercase tracking-wide text-accent-600 hover:bg-accent-500/10">
                        Join the Waitlist
                    </a>
                    <p class="mt-2 text-center text-xs text-slate-500">We email you if a seat opens up.</p>
                @elseif ($upcoming)
                    <p class="mt-6 rounded-full bg-slate-100 px-6 py-3 text-center font-display font-bold uppercase tracking-wide text-slate-500">
                        {{ $event->isSoldOut() ? 'Fully booked' : 'Registration closed' }}
                    </p>
                @endif

                @if ($whatsappUrl && $upcoming)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener"
                       class="mt-3 flex items-center justify-center gap-2 rounded-full border-2 border-[#25D366] px-6 py-2.5 font-display font-bold uppercase tracking-wide text-[#128C4B] hover:bg-[#25D366]/10">
                        <x-site.social-icon name="whatsapp" class="size-5" /> Ask on WhatsApp
                    </a>
                    <p class="mt-2 text-center text-xs text-slate-500">Questions about this hike? Message us and we'll get back to you.</p>
                @endif

                @unless ($upcoming)
                    <a href="{{ route('events.index') }}"
                       class="mt-6 block rounded-full bg-accent-500 px-6 py-3 text-center font-display font-bold uppercase tracking-wide text-white hover:bg-accent-600">
                        See upcoming hikes
                    </a>
                @endunless
            </div>
        </aside>
    </div>
</x-site.layout>
