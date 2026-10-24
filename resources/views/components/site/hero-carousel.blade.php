@props(['slides'])

{{-- $slides: App\Domain\Content\HomepageHero::slides() --}}

{{-- Autoplays every 6 s and pauses on hover/focus; arrows and dots to navigate. --}}
<section class="relative isolate overflow-hidden bg-brand-900 text-white" aria-roledescription="carousel" aria-label="Seasonal highlights"
         x-data="{
             current: 0,
             count: {{ count($slides) }},
             timer: null,
             go(i) { this.current = (i + this.count) % this.count },
             start() { this.stop(); this.timer = setInterval(() => this.go(this.current + 1), 6000) },
             stop() { clearInterval(this.timer) },
         }"
         x-init="start()" @mouseenter="stop()" @mouseleave="start()" @focusin="stop()" @focusout="start()">
    @foreach ($slides as $i => $slide)
        <div x-show="current === {{ $i }}" @if ($i > 0) x-cloak @endif
             x-transition:enter="transition-opacity duration-700" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             class="relative flex min-h-[70vh] items-center" role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ count($slides) }}">
            @if ($slide['image'])
                {{-- First slide is the largest above-the-fold image: load it first; the rest lazily. --}}
                <img src="{{ $slide['image'] }}" alt="" width="1920" height="1080" decoding="async"
                     @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif
                     class="absolute inset-0 -z-10 size-full object-cover">
                <div class="absolute inset-0 -z-10 bg-brand-900/60"></div>
            @else
                <div @class([
                    'absolute inset-0 -z-10 bg-linear-to-br',
                    match ($i % 3) {
                        0 => 'from-brand-900 via-brand-700 to-accent-600',
                        1 => 'from-slate-900 via-brand-800 to-sky-700',
                        default => 'from-brand-900 via-brand-600 to-emerald-400',
                    },
                ])></div>
                {{-- Mountain silhouette placeholder until real hero photos are added --}}
                <svg class="absolute inset-x-0 bottom-0 -z-10 h-1/2 w-full text-black/25" viewBox="0 0 1440 320" preserveAspectRatio="none" fill="currentColor" aria-hidden="true">
                    <path d="M0 320 240 120l160 120 200-200 220 220 180-140 200 160 240-180v220z"/>
                </svg>
            @endif

            <div class="mx-auto w-full max-w-7xl px-4 py-24 sm:px-6">
                @if ($slide['eyebrow'])
                    <p class="font-display text-sm font-semibold uppercase tracking-[0.25em] text-accent-400">{{ $slide['eyebrow'] }}</p>
                @endif
                <h2 class="mt-4 max-w-3xl font-display text-4xl font-bold uppercase leading-tight tracking-wide sm:text-6xl">{{ $slide['heading'] }}</h2>
                @if ($slide['text'])
                    <p class="mt-4 max-w-xl text-lg text-white/85">{{ $slide['text'] }}</p>
                @endif
                @if ($slide['cta'] && $slide['url'])
                    <a href="{{ $slide['url'] }}"
                       class="mt-10 inline-block rounded-full bg-accent-500 px-8 py-3 font-display font-bold uppercase tracking-wide hover:bg-accent-600">
                        {{ $slide['cta'] }}
                    </a>
                @endif
            </div>
        </div>
    @endforeach

    @if (count($slides) > 1)
        <button type="button" @click="go(current - 1)" aria-label="Previous slide"
                class="absolute left-3 top-1/2 hidden -translate-y-1/2 rounded-full bg-black/30 p-2 hover:bg-black/50 sm:block">
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
        </button>
        <button type="button" @click="go(current + 1)" aria-label="Next slide"
                class="absolute right-3 top-1/2 hidden -translate-y-1/2 rounded-full bg-black/30 p-2 hover:bg-black/50 sm:block">
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
        </button>
        <div class="absolute inset-x-0 bottom-6 flex justify-center gap-2">
            @foreach ($slides as $i => $slide)
                <button type="button" @click="go({{ $i }})" aria-label="Go to slide {{ $i + 1 }}"
                        class="h-2.5 rounded-full transition-all" :class="current === {{ $i }} ? 'w-8 bg-accent-500' : 'w-2.5 bg-white/60 hover:bg-white'"></button>
            @endforeach
        </div>
    @endif
</section>
