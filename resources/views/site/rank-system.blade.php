@php
    use App\Domain\Loyalty\Enums\RankTier;

    $icons = ['pathfinder' => '🧭', 'trekker' => '🥾', 'mountaineer' => '⛰️', 'highlander' => '🏔️', 'elite' => '👑'];

    // Worked example: a Level 2 hike booked early, and how many of those reach the second tier.
    $example = $hikePoints[1]['points'] + $earlyBooking['points'];
    $secondTier = RankTier::cases()[1];
    $exampleHikes = (int) ceil($secondTier->minPoints() / max(1, $example));
@endphp

<x-site.layout title="Rank System" description="Hike with us, earn points and climb the ranks from Pathfinder to Elite. How points are earned, what each rank unlocks and the terms.">
    <section class="bg-brand-800 px-4 py-16 text-center text-white sm:px-6">
        <p class="font-display text-sm font-semibold uppercase tracking-[0.2em] text-accent-400">Hike · Earn · Climb</p>
        <h1 class="mt-2 font-display text-4xl font-bold uppercase tracking-wide sm:text-5xl">Rank System</h1>
        <p class="mx-auto mt-4 max-w-2xl text-white/80">Every hike you complete earns points. Points lift you through five ranks, and every rank unlocks rewards.</p>
    </section>

    {{-- Your rank --}}
    <section class="mx-auto max-w-3xl px-4 pt-12 sm:px-6" aria-label="Your rank">
        @if ($hiker)
            @php($tier = $hiker->tier())
            <div class="rounded-2xl bg-brand-50 p-6 ring-1 ring-brand-100">
                <div class="flex flex-wrap items-center gap-4">
                    <span class="text-4xl" aria-hidden="true">{{ $icons[$tier->value] }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-slate-600">Your rank</p>
                        <p class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">{{ $tier->label() }} · {{ number_format($hiker->totalPoints()) }} points</p>
                    </div>
                    <a href="{{ route('account') }}" class="font-display text-sm font-semibold uppercase tracking-wide text-brand-600 hover:text-brand-800">My account &rarr;</a>
                </div>
                @if ($next = $tier->next())
                    <div class="mt-4 h-3 overflow-hidden rounded-full bg-white" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $hiker->tierProgressPercent() }}" aria-label="Progress to {{ $next->label() }}">
                        <div class="h-full rounded-full bg-accent-500" style="width: {{ $hiker->tierProgressPercent() }}%"></div>
                    </div>
                    <p class="mt-2 text-sm text-slate-600">{{ $hiker->pointsToNextTier() }} points to {{ $next->label() }}.</p>
                @else
                    <p class="mt-3 text-sm text-slate-600">You reached the top rank. Thank you for hiking with us!</p>
                @endif
            </div>
        @else
            <div class="rounded-2xl bg-brand-50 p-6 text-center ring-1 ring-brand-100">
                <p class="text-slate-700">Points are counted for everyone who books with their phone number. <strong>Log in</strong> to see your rank and rewards.</p>
                <div class="mt-4 flex flex-wrap justify-center gap-4">
                    <a href="{{ route('login') }}" class="rounded-full bg-brand-600 px-6 py-2.5 font-display text-sm font-semibold uppercase tracking-wide text-white hover:bg-brand-700">Log in</a>
                    <a href="{{ route('events.index') }}" class="rounded-full border border-brand-600 px-6 py-2.5 font-display text-sm font-semibold uppercase tracking-wide text-brand-700 hover:bg-white">Book a hike</a>
                </div>
            </div>
        @endif
    </section>

    {{-- Tiers and rewards --}}
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6" aria-labelledby="tiers-heading">
        <x-site.section-heading eyebrow="Five ranks"><span id="tiers-heading">The Ranks and Their Rewards</span></x-site.section-heading>
        <ol class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-5">
            @foreach ($tiers as ['tier' => $tier, 'rewards' => $rewards])
                <li @class([
                    'flex flex-col rounded-2xl p-6 ring-1',
                    'bg-white ring-slate-200' => ! ($hiker && $hiker->tier() === $tier),
                    'bg-accent-500/10 ring-2 ring-accent-500' => $hiker && $hiker->tier() === $tier,
                ])>
                    <p class="text-4xl" aria-hidden="true">{{ $icons[$tier->value] }}</p>
                    <h3 class="mt-3 font-display text-xl font-bold uppercase tracking-wide text-brand-900">{{ $tier->label() }}</h3>
                    <p class="font-semibold text-accent-600">{{ $tier->minPoints() === 0 ? 'From your first hike' : number_format($tier->minPoints()).'+ points' }}</p>
                    @if ($hiker && $hiker->tier() === $tier)
                        <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-brand-700">You are here</p>
                    @endif
                    @if ($rewards !== [])
                        <ul class="mt-4 space-y-3 text-sm">
                            @foreach ($rewards as $reward)
                                <li>
                                    <p class="font-semibold text-slate-900">🎁 {{ $reward->label }}</p>
                                    <p class="text-slate-600">{{ $reward->howItWorks() }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-4 text-sm text-slate-600">Where every hiker starts. Your points count from the first completed hike.</p>
                    @endif
                </li>
            @endforeach
        </ol>
    </section>

    {{-- How to earn points --}}
    <section class="bg-slate-50 py-16" aria-labelledby="points-heading">
        <div class="mx-auto max-w-5xl px-4 sm:px-6">
            <x-site.section-heading eyebrow="Every step counts"><span id="points-heading">How to Earn Points</span></x-site.section-heading>
            <div class="mt-12 grid gap-8 lg:grid-cols-2">
                <div class="overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200">
                    <table class="w-full text-left">
                        <caption class="sr-only">Points per completed hike by difficulty level</caption>
                        <thead class="bg-brand-800 text-sm uppercase tracking-wide text-white">
                            <tr><th class="px-5 py-3 font-display">Completed hike</th><th class="px-5 py-3 text-right font-display">Points</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($hikePoints as ['level' => $level, 'points' => $points])
                                <tr>
                                    <td class="px-5 py-3">{{ $level->label() }}</td>
                                    <td class="px-5 py-3 text-right font-display text-lg font-bold text-brand-800">+{{ $points }}</td>
                                </tr>
                            @endforeach
                            <tr class="bg-brand-50/50">
                                <td class="px-5 py-3">Early booking bonus <span class="block text-sm text-slate-500">Book at least {{ $earlyBooking['days'] }} days before the hike</span></td>
                                <td class="px-5 py-3 text-right font-display text-lg font-bold text-brand-800">+{{ $earlyBooking['points'] }}</td>
                            </tr>
                            <tr class="bg-brand-50/50">
                                <td class="px-5 py-3">Invite a friend <span class="block text-sm text-slate-500">When a new hiker gives your phone number as "Invited by" and completes their first hike</span></td>
                                <td class="px-5 py-3 text-right font-display text-lg font-bold text-brand-800">+{{ $referral }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="space-y-4 text-slate-700">
                    <p><strong>Points are added after the hike</strong>, when the organizers mark you as attended{{ $paidOnly ? ' and the hike is paid (free hikes always count)' : '' }}. You can follow every point on My account.</p>
                    <p><strong>Harder hikes earn more.</strong> The level of every hike is on its page; the <a href="{{ route('guide.difficulty') }}" class="font-medium text-brand-600 underline">difficulty calculator</a> explains how it is worked out.</p>
                    <div class="rounded-2xl bg-white p-5 ring-1 ring-slate-200">
                        <p class="font-display font-semibold uppercase tracking-wide text-brand-800">Example</p>
                        <p class="mt-2">A {{ $hikePoints[1]['level']->label() }} hike booked early: {{ $hikePoints[1]['points'] }} + {{ $earlyBooking['points'] }} = <strong>{{ $example }} points</strong>.
                            About {{ $exampleHikes }} of those take you to {{ $secondTier->label() }}.</p>
                    </div>
                    <p>Ranks are on the <a href="{{ route('leaderboard') }}" class="font-medium text-brand-600 underline">leaderboard</a>. Hikers with the same points are ranked by the distance they hiked. Prefer to stay off it? Choose "Hide me" on My account.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Terms --}}
    <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6" aria-labelledby="terms-heading">
        <x-site.section-heading eyebrow="The fine print"><span id="terms-heading">Terms</span></x-site.section-heading>
        <ul class="mt-8 list-disc space-y-3 pl-6 text-slate-700">
            <li>Points and rewards belong to one hiker, identified by the phone number used to book. They can't be transferred or exchanged for cash.</li>
            <li>A reward is used on a booking you make yourself, logged in, with your own phone number. One reward per booking; rewards can't be combined.</li>
            <li>Rewards are one-time unless their description says otherwise. Cancelling a booking gives its reward back.</li>
            <li>Points come from completed hikes only. If a hike is marked as not attended, cancelled{{ $paidOnly ? ' or unpaid' : '' }} afterwards, its points are removed.</li>
            <li>If your points drop below a rank, the unused rewards of that rank are locked again until you climb back.</li>
            <li>Points don't expire. The club may correct points that were added by mistake and may change the rank system; changes are announced on this page.</li>
        </ul>
        <div class="mt-10 text-center">
            <a href="{{ route('events.index') }}" class="inline-block rounded-full bg-accent-500 px-8 py-3 font-display font-bold uppercase tracking-wide text-white hover:bg-accent-600">Start earning: see upcoming hikes</a>
        </div>
    </section>
</x-site.layout>
