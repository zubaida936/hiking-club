<x-site.layout title="Leaderboard" description="The hikers with the most points this season: completed hikes, distance and rank.">
    <section class="bg-brand-800 px-4 py-16 text-center text-white sm:px-6">
        <p class="font-display text-sm font-semibold uppercase tracking-[0.2em] text-accent-400">Rank system</p>
        <h1 class="mt-2 font-display text-4xl font-bold uppercase tracking-wide sm:text-5xl">Leaderboard</h1>
        <p class="mx-auto mt-4 max-w-2xl text-white/80">Every completed hike earns points. Equal points are ranked by distance hiked.</p>
    </section>

    <div class="mx-auto max-w-3xl px-4 py-14 sm:px-6">
        @if ($me)
            <p class="mb-6 rounded-xl bg-brand-50 px-5 py-4 text-brand-900" role="status">
                @if ($myPosition)
                    You're <strong>#{{ $myPosition }}</strong> with {{ $me->totalPoints() }} points.
                @elseif (! $me->show_on_leaderboard)
                    You're hidden from the leaderboard. You can change that on <a href="{{ route('account') }}" class="font-semibold underline">My account</a>.
                @else
                    Complete a hike to appear on the leaderboard.
                @endif
            </p>
        @endif

        @if ($hikers->isEmpty())
            <p class="rounded-2xl bg-slate-50 p-10 text-center text-slate-600">The leaderboard fills up as hikers complete hikes and earn points.</p>
        @else
            <div class="overflow-hidden rounded-2xl ring-1 ring-slate-200">
                <x-site.leaderboard-list :hikers="$hikers" :highlight="$me?->id" />
            </div>
        @endif

        <p class="mt-8 text-center text-sm text-slate-500">
            Ranks: @foreach ($tiers as $tier){{ $tier->label() }} {{ $tier->minPoints() }}+{{ $loop->last ? '' : ' · ' }}@endforeach.
            Names are shown as first name and initial; members can hide themselves on My account.
        </p>
    </div>
</x-site.layout>
