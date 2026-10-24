@php
    $keys = array_keys($adventures);
@endphp

<x-site.layout title="Equipment Guide" description="What to pack for hiking, snowshoeing, rafting and camping in Lebanon: checklists for every difficulty level, from easy walks to Xtreme days.">
    <section class="bg-brand-800 px-4 py-16 text-center text-white sm:px-6">
        <p class="font-display text-sm font-semibold uppercase tracking-[0.2em] text-accent-400">Hiker's Guide</p>
        <h1 class="mt-2 font-display text-4xl font-bold uppercase tracking-wide sm:text-5xl">Equipment Guide</h1>
        <p class="mx-auto mt-4 max-w-2xl text-white/80">What you need for each adventure. Tick the items off while you pack.</p>
    </section>

    {{-- Tabs per adventure (#hiking, #camping... open a tab directly); hiking items filter by difficulty level. --}}
    <section class="mx-auto max-w-4xl px-4 py-14 sm:px-6"
             x-data="{ tab: @js($keys[0]), level: 1 }"
             x-init="const hash = location.hash.slice(1); if (@js($keys).includes(hash)) tab = hash">
        <x-site.section-heading eyebrow="Pack smart" class="mb-10">What You Need for Each Adventure</x-site.section-heading>

        <div class="flex flex-wrap justify-center gap-2" role="tablist" aria-label="Adventures">
            @foreach ($adventures as $key => $adventure)
                <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}"
                        :aria-selected="tab === @js($key)" @click="tab = @js($key); history.replaceState(null, '', '#{{ $key }}')"
                        class="rounded-full px-5 py-2 font-display text-sm font-semibold uppercase tracking-wide ring-1 ring-brand-600"
                        :class="tab === @js($key) ? 'bg-brand-600 text-white' : 'text-brand-700 hover:bg-brand-50'">
                    <span aria-hidden="true">{{ $adventure['icon'] }}</span> {{ $adventure['label'] }}
                </button>
            @endforeach
        </div>

        @foreach ($adventures as $key => $adventure)
            @php($byLevel = collect($adventure['items'])->contains(fn ($item) => $item[2] !== null))
            <div id="panel-{{ $key }}" role="tabpanel" aria-labelledby="tab-{{ $key }}" x-show="tab === @js($key)" @unless ($loop->first) x-cloak @endunless class="mt-10">
                <h2 class="font-display text-2xl font-bold uppercase tracking-wide text-brand-900">
                    <span aria-hidden="true">{{ $adventure['icon'] }}</span> {{ $adventure['label'] }}
                </h2>
                <p class="mt-2 text-slate-600">{{ $adventure['intro'] }}</p>

                @if ($byLevel)
                    <fieldset class="mt-6">
                        <legend class="text-xs font-semibold uppercase tracking-wide text-slate-500">Your hike's difficulty level</legend>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($levels as $option)
                                <label class="cursor-pointer rounded-full px-4 py-2 text-sm ring-1 ring-slate-300"
                                       :class="level === {{ $option->value }} ? 'bg-accent-500 text-white ring-accent-500' : 'hover:bg-slate-50'">
                                    <input type="radio" name="level-{{ $key }}" value="{{ $option->value }}" x-model.number="level" class="sr-only">
                                    {{ $option->label() }}
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-2 text-sm text-slate-500" x-text="{{ Js::from(collect($levels)->mapWithKeys(fn ($l) => [$l->value => $l->description()])) }}[level]"></p>
                    </fieldset>
                @endif

                <ul class="mt-6 divide-y divide-slate-100 rounded-2xl ring-1 ring-slate-200">
                    @foreach ($adventure['items'] as [$name, $tip, $fromLevel, $shop])
                        <li class="flex items-start gap-4 p-4" @if ($fromLevel) x-show="level >= {{ $fromLevel }}" @endif>
                            <input type="checkbox" id="item-{{ $key }}-{{ $loop->index }}" class="mt-1 size-5 shrink-0 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <label for="item-{{ $key }}-{{ $loop->index }}" class="min-w-0 flex-1">
                                <span class="font-semibold text-slate-900">{{ $name }}</span>
                                @if ($fromLevel)
                                    <span class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Level {{ $fromLevel }}+</span>
                                @endif
                                <span class="block text-sm text-slate-600">{{ $tip }}</span>
                            </label>
                            @if ($shop && isset($shopCategories[$shop]))
                                <a href="{{ route('shop.index', ['category' => $shop]) }}" class="shrink-0 text-sm font-semibold text-accent-600 hover:text-accent-500">
                                    Shop<span class="sr-only"> {{ $shopCategories[$shop] }}</span> &rarr;
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach

        <div class="mt-12 rounded-2xl bg-brand-50 p-6 text-center">
            <p class="text-slate-700">Not sure which level a hike is? Every event shows its level, and the calculator explains how it is worked out.</p>
            <div class="mt-4 flex flex-wrap justify-center gap-4">
                <a href="{{ route('events.index') }}" class="font-display font-semibold uppercase tracking-wide text-brand-600 hover:text-brand-800">Upcoming hikes &rarr;</a>
                <a href="{{ route('guide.difficulty') }}" class="font-display font-semibold uppercase tracking-wide text-brand-600 hover:text-brand-800">Difficulty calculator &rarr;</a>
            </div>
        </div>
    </section>
</x-site.layout>
