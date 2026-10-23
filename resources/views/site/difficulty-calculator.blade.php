@php
    $levelData = collect($levels)->map(fn ($level) => [
        'value' => $level->value,
        'label' => $level->label(),
        'description' => $level->description(),
        'points' => (int) config("points.hike.{$level->value}"),
        'classes' => $level->badgeClasses(),
    ])->values();
    $bounds = collect($calculator['levels']);
    $fmt = fn (?float $n) => $n === null ? '' : rtrim(rtrim(number_format($n, 1, '.', ''), '0'), '.');
@endphp

<x-site.layout title="Difficulty Calculator" description="Work out the effort score and difficulty level of a hike from its distance and elevation, the same way we rate our hikes.">
    <section class="bg-brand-800 px-4 py-16 text-center text-white sm:px-6">
        <p class="font-display text-sm font-semibold uppercase tracking-[0.2em] text-accent-400">Hiker's guide</p>
        <h1 class="mt-2 font-display text-4xl font-bold uppercase tracking-wide sm:text-5xl">Difficulty Calculator</h1>
        <p class="mx-auto mt-4 max-w-2xl text-white/80">Enter a route's distance and climbing to see its effort score and level, exactly how we rate our hikes.</p>
    </section>

    <div class="mx-auto max-w-4xl px-4 py-14 sm:px-6"
         x-data="difficultyCalculator({ calculator: @js($calculator), levels: @js($levelData), km: @js($fmt($input['km'])), gain: @js($fmt($input['gain'])), loss: @js($fmt($input['loss'])) })">
        <div class="grid gap-8 md:grid-cols-2">
            {{-- Inputs: a plain GET form, so it also works (and can be shared) without JavaScript. --}}
            <form method="GET" action="{{ route('guide.difficulty') }}#result" class="space-y-5 rounded-2xl border border-slate-200 p-6">
                <div>
                    <label for="km" class="block text-sm font-semibold text-slate-700">Distance (km)</label>
                    <input id="km" name="km" type="number" step="0.1" min="0.1" max="200" required inputmode="decimal" x-model="km" value="{{ $fmt($input['km']) }}"
                           class="mt-1 block w-full rounded-xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500" placeholder="e.g. 12">
                </div>
                <div>
                    <label for="gain" class="block text-sm font-semibold text-slate-700">Elevation gain (m)</label>
                    <input id="gain" name="gain" type="number" step="10" min="0" max="9000" inputmode="numeric" x-model="gain" value="{{ $fmt($input['gain']) }}"
                           class="mt-1 block w-full rounded-xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500" placeholder="e.g. 600">
                    <p class="mt-1 text-xs text-slate-500">Total climbing (the "+" on your GPS or map app).</p>
                </div>
                <div>
                    <label for="loss" class="block text-sm font-semibold text-slate-700">Elevation loss (m) <span class="font-normal text-slate-400">(optional)</span></label>
                    <input id="loss" name="loss" type="number" step="10" min="0" max="9000" inputmode="numeric" x-model="loss" value="{{ $fmt($input['loss']) }}"
                           class="mt-1 block w-full rounded-xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500" placeholder="e.g. 400">
                    <p class="mt-1 text-xs text-slate-500">Total descent. On a loop it's about the same as the gain; empty counts as 0.</p>
                </div>
                <noscript>
                    <button class="w-full rounded-full bg-accent-500 px-8 py-3 font-display font-bold uppercase tracking-wide text-white hover:bg-accent-600">Calculate</button>
                </noscript>
            </form>

            {{-- Result --}}
            <section id="result" aria-live="polite" class="scroll-mt-24 rounded-2xl bg-brand-50 p-6">
                <h2 class="font-display text-xl font-bold uppercase tracking-wide text-brand-900">Result</h2>

                {{-- Server-rendered result (no JavaScript / shared link); replaced by the live one below. --}}
                @if ($result)
                    <div x-show="false">
                        <p class="mt-4 font-display text-5xl font-bold text-brand-900">{{ $result->effortScore }}</p>
                        <p class="text-sm text-slate-500">effort score</p>
                        <p class="mt-4"><span class="rounded-full px-3 py-1 text-sm font-semibold {{ $result->level->badgeClasses() }}">{{ $result->level->label() }}</span></p>
                        <p class="mt-3 text-slate-700">{{ $result->level->description() }}</p>
                    </div>
                @endif

                <div x-cloak x-show="ready">
                    <p class="mt-4 font-display text-5xl font-bold text-brand-900" x-text="score"></p>
                    <p class="text-sm text-slate-500">effort score · like <span x-text="equivalentKm.toFixed(1)"></span> km on the flat</p>
                    <p class="mt-4"><span class="rounded-full px-3 py-1 text-sm font-semibold" :class="level.classes" x-text="level.label"></span></p>
                    <p class="mt-3 text-slate-700" x-text="level.description"></p>
                    <p class="mt-3 text-sm text-slate-600">Completing a hike at this level earns <strong class="text-accent-600" x-text="level.points + ' points'"></strong>.</p>
                </div>
                @unless ($result)
                    <p class="mt-4 text-slate-600" x-show="!ready">Enter a distance to see the score.</p>
                @endunless
            </section>
        </div>

        @if ($similar->isNotEmpty())
            <section class="mt-12" aria-labelledby="similar-heading">
                <h2 id="similar-heading" class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">Upcoming {{ $result?->level->label() }} hikes</h2>
                <ul class="mt-4 divide-y divide-slate-100 rounded-2xl border border-slate-200">
                    @foreach ($similar as $event)
                        <li class="flex items-center justify-between gap-4 px-5 py-3">
                            <a href="{{ route('events.show', $event) }}" class="font-semibold text-brand-900 hover:underline">{{ $event->title }}</a>
                            <span class="shrink-0 text-sm text-slate-500">{{ $event->starts_on->format('D j M') }} · {{ $event->formattedDistance() }} · score {{ $event->effort_score }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- How it works --}}
        <section class="mt-14 grid gap-10 md:grid-cols-2" aria-labelledby="how-heading">
            <div>
                <h2 id="how-heading" class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">How it works</h2>
                <p class="mt-4 text-slate-700">Climbing tires you more than walking on the flat, so we turn every route into an "equivalent distance":</p>
                <p class="mt-3 rounded-xl bg-slate-50 px-4 py-3 font-mono text-sm text-slate-800">
                    score = {{ $fmt((float) $calculator['multiplier']) }} × (km + gain ÷ {{ $fmt((float) $calculator['gainPerKm']) }} + loss ÷ {{ $fmt((float) $calculator['lossPerKm']) }})
                </p>
                <p class="mt-3 text-slate-700">
                    Every {{ $fmt((float) $calculator['gainPerKm']) }} m of climbing counts like one more kilometre, and every
                    {{ $fmt((float) $calculator['lossPerKm']) }} m of descent too (going down is tiring, but less than going up).
                    Example: 10 km with +500 m and −500 m scores {{ app(\App\Domain\Events\Difficulty\DifficultyCalculator::class)->calculate(10, 500, 500)->effortScore }}.
                </p>
            </div>
            <div>
                <h2 class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">Levels</h2>
                <ul class="mt-4 space-y-3">
                    @foreach ($levels as $i => $level)
                        @php
                            $min = $i === 0 ? 0 : $bounds[$i - 1]['max'] + 1;
                            $max = $bounds[$i]['max'];
                        @endphp
                        <li class="rounded-xl border border-slate-200 p-4">
                            <p class="flex items-center justify-between gap-2">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $level->badgeClasses() }}">{{ $level->label() }}</span>
                                <span class="text-sm font-semibold text-slate-600">score {{ $max === null ? $min.'+' : $min.'–'.$max }}</span>
                            </p>
                            <p class="mt-2 text-sm text-slate-600">{{ $level->description() }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        <section class="mt-12 rounded-2xl border border-amber-200 bg-amber-50 p-6" aria-labelledby="limits-heading">
            <h2 id="limits-heading" class="font-display text-xl font-bold uppercase tracking-wide text-amber-900">What the score doesn't tell you</h2>
            <ul class="mt-3 list-disc space-y-1 pl-5 text-amber-950">
                <li><strong>Weather:</strong> heat, rain, wind or fog can make an easy route hard; snow changes everything.</li>
                <li><strong>Terrain:</strong> rocky, steep, muddy or off-trail ground is slower and more tiring than a good path.</li>
                <li><strong>Pace and breaks:</strong> the score is about effort, not time; a group walks at the pace of its slowest hiker.</li>
                <li><strong>Altitude and exposure:</strong> high ground, sun exposure and scrambling sections add difficulty.</li>
                <li><strong>Your fitness:</strong> use the level to choose, and start with a lower level if you're unsure.</li>
            </ul>
            <p class="mt-3 text-sm text-amber-900">Our guides add these to each hike's description. When in doubt, <a href="{{ route('events.index') }}" class="font-semibold underline">pick a hike</a> and ask us.</p>
        </section>
    </div>
</x-site.layout>
