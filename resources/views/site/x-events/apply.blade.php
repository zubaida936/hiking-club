@php
    $under = (int) config('xevents.guardian_under_age');
    $legend = 'font-display text-xl font-bold uppercase tracking-wide text-brand-900';
    $select = 'mt-1 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500';
@endphp

<x-site.layout title="Apply for X Events" :noindex="true">
    <section class="bg-slate-950 px-4 py-12 text-white sm:px-6">
        <div class="mx-auto max-w-3xl">
            <a href="{{ route('x-events') }}" class="font-display text-sm font-semibold uppercase tracking-wide text-white/70 hover:text-white">&larr; X Events</a>
            <h1 class="mt-4 font-display text-3xl font-bold uppercase tracking-wide sm:text-4xl">Apply for X Events</h1>
            <p class="mt-2 text-white/75">Apply once. Our guides review every application by hand; answer honestly, it helps us keep everyone safe.</p>
        </div>
    </section>

    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        @if (session('error'))
            <p class="mb-8 rounded-xl bg-red-50 px-5 py-4 font-medium text-red-800" role="alert">{{ session('error') }}</p>
        @endif

        <form method="POST" action="{{ route('x-events.apply.store') }}" class="space-y-12" novalidate x-data="{ age: @js(old('age')) }">
            @csrf

            <fieldset class="space-y-5">
                <legend class="{{ $legend }}">About you</legend>
                <x-site.field name="name" label="Full name" :value="$member?->name" required maxlength="120" autocomplete="name" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-site.field name="phone" label="WhatsApp number" type="tel" :value="$member?->phone" required placeholder="+961 70 123 456" autocomplete="tel"
                                  hint="We contact you here about your application." />
                    <x-site.field name="email" label="Email" type="email" :optional="true" :value="$member?->email" autocomplete="email" />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-site.field name="age" label="Age" type="number" min="14" max="90" required inputmode="numeric" x-model="age" />
                    <x-site.field name="city" label="City" required maxlength="80" autocomplete="address-level2" />
                </div>
                <div x-show="age === '' || age === null || Number(age) < {{ $under }}" class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm font-semibold text-amber-900">Under {{ $under }}? A parent or guardian must agree. (Leave empty if you're {{ $under }} or older.)</p>
                    <div class="mt-3 grid gap-5 sm:grid-cols-2">
                        <x-site.field name="guardian_name" label="Parent or guardian's name" maxlength="120" />
                        <x-site.field name="guardian_phone" label="Their phone" type="tel" placeholder="+961 3 123 456" />
                    </div>
                </div>
            </fieldset>

            <fieldset class="space-y-5">
                <legend class="{{ $legend }}">Your experience</legend>
                <div>
                    <label for="hike_frequency" class="block text-sm font-semibold text-slate-700">How often do you hike?</label>
                    <select id="hike_frequency" name="hike_frequency" required class="{{ $select }}">
                        <option value="">Choose…</option>
                        @foreach (config('xevents.frequency') as $key => $label)
                            <option value="{{ $key }}" @selected(old('hike_frequency') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('hike_frequency') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-sm font-semibold text-slate-700">Your hardest hike so far</p>
                    <div class="mt-3 grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2"><x-site.field name="hardest_route" label="Route" required maxlength="150" placeholder="e.g. Qornet es Sawda from Bcharre" /></div>
                        <x-site.field name="hardest_date" label="When" type="date" :optional="true" />
                        <x-site.field name="hardest_km" label="Distance (km)" type="number" step="0.1" min="1" required inputmode="decimal" />
                        <x-site.field name="hardest_elevation_m" label="Elevation gain (m)" type="number" min="0" required inputmode="numeric" />
                        <x-site.field name="hardest_duration_hours" label="Duration (hours)" type="number" step="0.5" min="0.5" required inputmode="decimal" />
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-site.field name="longest_km" label="Longest distance in one day (km)" type="number" step="0.1" min="1" required inputmode="decimal" />
                    <x-site.field name="max_ascent_m" label="Biggest ascent in one day (m)" type="number" min="0" required inputmode="numeric" />
                </div>

                <div>
                    <label for="pace" class="block text-sm font-semibold text-slate-700">Your usual pace</label>
                    <select id="pace" name="pace" required class="{{ $select }}">
                        <option value="">Choose…</option>
                        @foreach (config('xevents.pace') as $key => $label)
                            <option value="{{ $key }}" @selected(old('pace') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('pace') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <fieldset>
                    <legend class="text-sm font-semibold text-slate-700">Can you keep a steady pace for 8 hours or more?</legend>
                    <div class="mt-2 flex gap-6">
                        @foreach (['1' => 'Yes', '0' => 'No / not sure'] as $value => $label)
                            <label class="flex items-center gap-2 text-slate-700">
                                <input type="radio" name="steady_pace" value="{{ $value }}" class="text-brand-600 focus:ring-brand-500" @checked(old('steady_pace') === (string) $value)> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @error('steady_pace') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </fieldset>

                <div>
                    <label for="main_challenge" class="block text-sm font-semibold text-slate-700">What's your main challenge on long hikes?</label>
                    <textarea id="main_challenge" name="main_challenge" rows="3" maxlength="1000" required class="{{ $select }}">{{ old('main_challenge') }}</textarea>
                    @error('main_challenge') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <x-site.field name="track_link" label="Strava or Wikiloc profile / track" type="url" :optional="true" placeholder="https://" />

                <fieldset>
                    <legend class="text-sm font-semibold text-slate-700">Have you done… <span class="font-normal text-slate-400">(tick all that apply)</span></legend>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach (config('xevents.experience') as $key => $label)
                            <label class="flex items-center gap-2 text-slate-700">
                                <input type="checkbox" name="experience[]" value="{{ $key }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(in_array($key, (array) old('experience', []), true))> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </fieldset>

            <fieldset class="space-y-4">
                <legend class="{{ $legend }}">Gear and health</legend>
                <fieldset>
                    <legend class="text-sm font-semibold text-slate-700">Gear you own</legend>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach (config('xevents.gear') as $key => $label)
                            <label class="flex items-center gap-2 text-slate-700">
                                <input type="checkbox" name="gear[]" value="{{ $key }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(in_array($key, (array) old('gear', []), true))> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <div>
                    <label for="limitations" class="block text-sm font-semibold text-slate-700">Injuries, health conditions or other limitations <span class="font-normal text-slate-400">(optional)</span></label>
                    <textarea id="limitations" name="limitations" rows="3" maxlength="1000" class="{{ $select }}">{{ old('limitations') }}</textarea>
                </div>
            </fieldset>

            <div>
                <label class="flex items-start gap-3 text-sm text-slate-700">
                    <input type="checkbox" name="consent" value="1" required class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(old('consent'))>
                    <span>My answers are true. I understand X Events are demanding and I take part at my own risk. The club may keep these answers to assess
                        X Events access and contact me on WhatsApp. See our <a href="{{ route('legal.privacy') }}" target="_blank" class="text-brand-700 underline">privacy policy</a>.</span>
                </label>
                @error('consent') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="hidden" aria-hidden="true">
                <label for="website">Website</label>
                <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>

            <button class="w-full rounded-full bg-red-600 px-8 py-4 font-display text-lg font-bold uppercase tracking-wide text-white hover:bg-red-700 sm:w-auto">Send my application</button>
        </form>
    </div>
</x-site.layout>
