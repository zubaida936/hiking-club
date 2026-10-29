@php
    $km = fn (float $value) => rtrim(rtrim(number_format($value, 1), '0'), '.').' km';
@endphp

<x-site.layout title="My account" :noindex="true">
    <section class="bg-brand-800 px-4 py-12 text-white sm:px-6">
        <div class="mx-auto flex max-w-6xl flex-wrap items-end justify-between gap-4">
            <div>
                <p class="font-display text-sm font-semibold uppercase tracking-[0.2em] text-accent-400">My account</p>
                <h1 class="mt-1 font-display text-3xl font-bold uppercase tracking-wide sm:text-4xl">Hi {{ Str::before(auth()->user()->name, ' ') }}!</h1>
                @if ($hiker)
                    <p class="mt-2 text-white/85">{{ $hiker->tier()->label() }} · {{ $hiker->totalPoints() }} points</p>
                @endif
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-full border border-white/40 px-5 py-2 font-display text-sm font-semibold uppercase tracking-wide hover:bg-white/10">Log out</button>
            </form>
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        @if (session('success'))
            <p class="mb-8 rounded-xl bg-brand-50 px-5 py-4 font-medium text-brand-900" role="status">{{ session('success') }}</p>
        @endif
        @if (session('error'))
            <p class="mb-8 rounded-xl bg-red-50 px-5 py-4 font-medium text-red-800" role="alert">{{ session('error') }}</p>
        @endif

        @if ($hiker === null)
            <p class="text-slate-600">There are no hikes on this account yet.</p>
        @else
            @php
                $tier = $hiker->tier();
                $next = $tier->next();
            @endphp

            {{-- Rank & progress --}}
            <section aria-labelledby="rank-heading" class="rounded-2xl bg-brand-50 p-6 sm:p-8">
                <h2 id="rank-heading" class="sr-only">My rank</h2>
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">Rank</p>
                        <p class="font-display text-4xl font-bold text-brand-900">{{ $tier->label() }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Points</p>
                        <p class="font-display text-4xl font-bold text-accent-600">{{ $hiker->totalPoints() }}</p>
                    </div>
                </div>
                <div class="mt-5">
                    <div class="h-3 overflow-hidden rounded-full bg-white" role="progressbar" aria-label="Progress to the next rank"
                         aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $hiker->tierProgressPercent() }}">
                        <div class="h-full rounded-full bg-accent-500" style="width: {{ $hiker->tierProgressPercent() }}%"></div>
                    </div>
                    <p class="mt-2 text-sm text-slate-600">
                        @if ($next)
                            <strong>{{ $hiker->pointsToNextTier() }} points</strong> to {{ $next->label() }} ({{ $next->minPoints() }} points)
                        @else
                            You've reached the highest rank. Respect!
                        @endif
                    </p>
                </div>

                <dl class="mt-6 grid grid-cols-2 gap-4 border-t border-brand-100 pt-6 text-center sm:grid-cols-3">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Hikes completed</dt>
                        <dd class="font-display text-2xl font-bold text-brand-900">{{ $history->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Distance hiked</dt>
                        <dd class="font-display text-2xl font-bold text-brand-900">{{ $km($hiker->totalDistanceKm()) }}</dd>
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Leaderboard</dt>
                        <dd class="font-display text-2xl font-bold text-brand-900">{{ $position ? '#'.$position : '—' }}</dd>
                    </div>
                </dl>

                <form method="POST" action="{{ route('account.leaderboard') }}" class="mt-4 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-sm text-slate-600">
                    @csrf
                    <input type="hidden" name="show_on_leaderboard" value="{{ $hiker->show_on_leaderboard ? 0 : 1 }}">
                    <span>
                        @if ($hiker->show_on_leaderboard)
                            You appear on the <a href="{{ route('leaderboard') }}" class="font-semibold text-brand-700 hover:underline">public leaderboard</a> as "{{ $hiker->publicName() }}".
                        @else
                            You're hidden from the <a href="{{ route('leaderboard') }}" class="font-semibold text-brand-700 hover:underline">public leaderboard</a>.
                        @endif
                    </span>
                    <button class="font-semibold text-brand-700 underline hover:text-brand-900">{{ $hiker->show_on_leaderboard ? 'Hide me' : 'Show me' }}</button>
                </form>
            </section>

            <div class="mt-12 grid gap-12 lg:grid-cols-2">
                {{-- Upcoming --}}
                <section aria-labelledby="upcoming-heading">
                    <h2 id="upcoming-heading" class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">My upcoming hikes</h2>
                    @forelse ($upcoming as $booking)
                        <article class="mt-4 rounded-2xl border border-slate-200 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <a href="{{ route('registrations.show', $booking) }}" class="font-semibold text-brand-900 hover:underline">{{ $booking->event->title }}</a>
                                    <p class="text-sm text-slate-500">
                                        {{ $booking->event->starts_on->format('D, j M Y') }}{{ $booking->pickup_point ? ' · '.$booking->pickup_point.' '.$booking->pickupTimeShort() : '' }}
                                    </p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                    {{ $booking->status->label() }}{{ $booking->isWaitlisted() ? ' #'.$booking->waitlistPosition() : '' }}
                                </span>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                                @if ($booking->amountDueCents() === 0)
                                    <span class="text-slate-500">{{ $booking->event->fee_cents === 0 ? 'Free hike' : 'Free with your reward' }}</span>
                                @elseif ($booking->paid_at)
                                    <span class="font-semibold text-brand-700">✓ Paid</span>
                                @elseif ($booking->awaitsPayment())
                                    <a href="{{ route('registrations.show', $booking) }}#payment-heading" class="font-semibold text-accent-600 hover:underline">
                                        {{ $booking->formattedAmountDue() }} to pay &rarr;
                                    </a>
                                @endif
                                <a href="{{ route('registrations.show', $booking) }}" class="text-brand-700 hover:underline">Booking details</a>
                                @if (\App\Domain\Registrations\Actions\CancelOwnBooking::allowed($booking))
                                    <form method="POST" action="{{ route('account.bookings.cancel', $booking) }}" class="ml-auto text-right"
                                          onsubmit="return confirm('{{ $booking->paid_at ? 'Cancel this booking? We will refund you, and your seat goes to the next person.' : 'Cancel this booking? Your seat goes to the next person.' }}')">
                                        @csrf
                                        <button class="text-red-600 hover:underline">Cancel booking</button>
                                        <span class="block text-xs text-slate-500">
                                            until {{ \App\Domain\Registrations\Actions\CancelOwnBooking::lastMoment($booking) }}{{ $booking->paid_at ? ', with refund' : '' }}
                                        </span>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="mt-4 text-slate-600">No upcoming hikes booked. <a href="{{ route('events.index') }}" class="font-semibold text-brand-700 hover:underline">Find your next one</a></p>
                    @endforelse
                    @if ($upcoming->isNotEmpty())
                        <p class="mt-3 text-xs text-slate-500">
                            Paid bookings can be cancelled here until {{ config('site.booking.paid_cancellation_days') }} days before the hike, and we refund you.
                            After that, message us on WhatsApp.
                        </p>
                    @endif
                </section>

                {{-- Rewards --}}
                <section aria-labelledby="rewards-heading">
                    <h2 id="rewards-heading" class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">Rewards</h2>
                    <ul class="mt-4 space-y-3">
                        @foreach (collect($rewards)->groupBy(fn ($state) => $state['reward']->tier->value) as $states)
                            @php
                                $level = $states->first()['reward']->tier;
                            @endphp
                            <li class="rounded-2xl border border-slate-200 p-4">
                                <p class="font-semibold">{{ $level->label() }} <span class="font-normal text-slate-500">· {{ $level->minPoints() }}+ points</span></p>
                                <ul class="mt-2 space-y-2 text-sm">
                                    @foreach ($states as $state)
                                        @php
                                            $reward = $state['reward'];
                                            $usedUp = $reward->isCountable() && $state['uses_left'] === 0;
                                        @endphp
                                        <li class="flex flex-wrap items-baseline justify-between gap-x-3">
                                            <span @class(['text-slate-800' => $state['unlocked'] && ! $usedUp, 'text-slate-400' => ! $state['unlocked'] || $usedUp])>{{ $reward->label }}</span>
                                            <span class="text-xs font-semibold">
                                                @if ($usedUp && $reward->kind === \App\Domain\Rewards\Reward::BONUS_POINTS)
                                                    <span class="text-brand-700">✓ Added to your points</span>
                                                @elseif ($usedUp)
                                                    <span class="text-slate-500">Used{{ $state['redemptions']->first()?->registration ? ' · '.$state['redemptions']->first()->registration->event->title : '' }}</span>
                                                @elseif (! $state['unlocked'])
                                                    <span class="text-slate-400">🔒 {{ $state['locked_reason'] }}</span>
                                                @elseif ($reward->kind === \App\Domain\Rewards\Reward::DISCOUNT)
                                                    <span class="text-brand-700">Available{{ $reward->uses > 1 ? ' · '.$state['uses_left'].' left' : '' }} · choose it when you book</span>
                                                @elseif ($reward->kind === \App\Domain\Rewards\Reward::PERK)
                                                    <span class="text-brand-700">Available · ask an organizer</span>
                                                @elseif ($reward->kind === \App\Domain\Rewards\Reward::XTREME)
                                                    <span class="text-brand-700">Active · your X event bookings are confirmed right away</span>
                                                @else
                                                    <span class="text-brand-700">Unlocked</span>
                                                @endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-3 text-xs text-slate-500">Rewards are personal, one-time unless stated, and can't be combined (one per booking). A reward used on a booking you cancel is given back. <a href="{{ route('rank-system') }}" class="font-medium text-brand-600 underline">How the rank system works</a></p>
                </section>
            </div>

            <div class="mt-12 grid gap-12 lg:grid-cols-2">
                {{-- History --}}
                <section aria-labelledby="history-heading">
                    <h2 id="history-heading" class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">Hike history</h2>
                    @if ($history->isEmpty())
                        <p class="mt-4 text-slate-600">Your completed hikes will appear here.</p>
                    @else
                        <p class="mt-1 text-sm text-slate-500">{{ $history->count() }} {{ Str::plural('hike', $history->count()) }} · {{ $km($hiker->totalDistanceKm()) }}</p>
                        <ul class="mt-4 divide-y divide-slate-100">
                            @foreach ($history as $hike)
                                <li class="flex items-center justify-between gap-4 py-3 text-sm">
                                    <span>
                                        <a href="{{ route('events.show', $hike->event) }}" class="font-semibold text-brand-900 hover:underline">{{ $hike->event->title }}</a>
                                        <span class="block text-slate-500">{{ $hike->event->starts_on->format('j M Y') }} · {{ $hike->event->formattedDistance() }} · {{ $hike->event->difficulty_level->label() }}</span>
                                    </span>
                                    <span class="shrink-0 font-semibold text-accent-600">+{{ (int) $hike->earned_points }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                {{-- Latest points --}}
                <section aria-labelledby="points-heading">
                    <h2 id="points-heading" class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">Latest points</h2>
                    @forelse ($recentPoints as $line)
                        <div class="mt-3 flex justify-between gap-4 border-b border-slate-100 pb-3 text-sm">
                            <span class="text-slate-700">{{ $line->description }}</span>
                            <span @class(['shrink-0 font-semibold', 'text-brand-700' => $line->points > 0, 'text-red-600' => $line->points < 0])>{{ $line->points > 0 ? '+' : '' }}{{ $line->points }}</span>
                        </div>
                    @empty
                        <p class="mt-4 text-slate-600">You earn points for every hike you complete with us.</p>
                    @endforelse
                </section>
            </div>
        @endif

        <p class="mt-16 border-t border-slate-100 pt-6 text-sm text-slate-500">
            <a href="{{ route('account.profile') }}" class="font-semibold text-brand-700 hover:underline">Edit my profile</a>
            · <a href="{{ route('account.delete') }}" class="font-semibold text-red-700 hover:underline">Delete my account</a>
            · <a href="{{ route('delete-account') }}" class="hover:underline">what happens to my data</a>
        </p>
    </div>
</x-site.layout>
