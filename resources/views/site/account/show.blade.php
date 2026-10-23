<x-site.layout title="My account" :noindex="true">
    <section class="bg-brand-800 px-4 py-12 text-white sm:px-6">
        <div class="mx-auto flex max-w-5xl flex-wrap items-end justify-between gap-4">
            <div>
                <p class="font-display text-sm font-semibold uppercase tracking-[0.2em] text-accent-400">My account</p>
                <h1 class="mt-1 font-display text-3xl font-bold uppercase tracking-wide sm:text-4xl">Hi {{ Str::before(auth()->user()->name, ' ') }}!</h1>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-full border border-white/40 px-5 py-2 font-display text-sm font-semibold uppercase tracking-wide hover:bg-white/10">Log out</button>
            </form>
        </div>
    </section>

    <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6">
        @if (session('success'))
            <p class="mb-8 rounded-xl bg-brand-50 px-5 py-4 font-medium text-brand-900" role="status">{{ session('success') }}</p>
        @endif

        @if ($hiker === null)
            <p class="text-slate-600">There are no hikes on this account yet.</p>
        @else
            @php($tier = $hiker->tier())
            <div class="grid gap-6 sm:grid-cols-3">
                <div class="rounded-2xl bg-brand-50 p-6">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Points</p>
                    <p class="font-display text-4xl font-bold text-brand-900">{{ $hiker->totalPoints() }}</p>
                </div>
                <div class="rounded-2xl bg-brand-50 p-6">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Rank</p>
                    <p class="font-display text-4xl font-bold text-brand-900">{{ $tier->label() }}</p>
                    @if ($hiker->pointsToNextTier() !== null)
                        <p class="mt-1 text-sm text-slate-600">{{ $hiker->pointsToNextTier() }} points to {{ $tier->next()?->label() }}</p>
                    @endif
                </div>
                <div class="rounded-2xl bg-brand-50 p-6">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Leaderboard</p>
                    <p class="font-display text-4xl font-bold text-brand-900">#{{ $position }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ rtrim(rtrim(number_format($hiker->totalDistanceKm(), 1), '0'), '.') }} km hiked</p>
                </div>
            </div>

            <div class="mt-12 grid gap-10 lg:grid-cols-2">
                <section aria-labelledby="upcoming-heading">
                    <h2 id="upcoming-heading" class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">My upcoming hikes</h2>
                    @forelse ($upcoming as $booking)
                        <a href="{{ route('registrations.show', $booking) }}" class="mt-4 flex items-center justify-between gap-4 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50">
                            <span>
                                <span class="block font-semibold text-brand-900">{{ $booking->event->title }}</span>
                                <span class="text-sm text-slate-500">{{ $booking->event->starts_on->format('D, j M Y') }}{{ $booking->pickup_point ? ' · '.$booking->pickup_point.' '.$booking->pickupTimeShort() : '' }}</span>
                            </span>
                            <span class="shrink-0 text-sm font-semibold text-slate-600">{{ $booking->status->label() }}</span>
                        </a>
                    @empty
                        <p class="mt-4 text-slate-600">No upcoming hikes booked. <a href="{{ route('events.index') }}" class="font-semibold text-brand-700 hover:underline">Find your next one</a></p>
                    @endforelse
                </section>

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
    </div>
</x-site.layout>
