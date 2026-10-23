@php
    $meetingPoints = old('meeting_points', $event->meetingPoints->map(fn ($p) => [
        'name' => $p->name,
        'pickup_time' => $p->pickupTimeShort(),
    ])->all());
@endphp

<div class="grid gap-6 lg:grid-cols-3"
     x-data="eventForm({
        calculator: @js($calculator),
        distance: @js(old('distance_km', $event->distance_km)),
        gain: @js(old('elevation_gain_m', $event->elevation_gain_m)),
        loss: @js(old('elevation_loss_m', $event->elevation_loss_m)),
        meetingPoints: @js(array_values($meetingPoints)),
     })">

    {{-- Main column --}}
    <div class="space-y-6 lg:col-span-2">
        <section class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold">Basics</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-admin.input name="title" label="Title" :value="$event->title" required maxlength="150" />
                </div>
                <x-admin.input name="slug" label="URL slug" :value="$event->slug" maxlength="170"
                               hint="Leave empty to generate from the title." />
                <x-admin.input name="season_tag" label="Season tag" :value="$event->season_tag" maxlength="40"
                               hint="Shown on the event card, e.g. 🍂 Autumn Trail" />
                <x-admin.input name="location" label="Location" :value="$event->location" required maxlength="120" />
                <x-admin.input name="starts_on" label="Date" type="date" :value="$event->starts_on?->format('Y-m-d')" required />
                <div class="sm:col-span-2">
                    <x-admin.textarea name="summary" label="Short summary" :value="$event->summary" rows="2" maxlength="300"
                                      hint="One or two sentences for event cards and social sharing." />
                </div>
                <div class="sm:col-span-2">
                    <x-admin.textarea name="description" label="Description" :value="$event->description" rows="8" />
                </div>
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold">Route</h2>
            <div class="grid gap-4 sm:grid-cols-4">
                <x-admin.input name="distance_km" label="Distance (km)" type="number" step="0.1" min="0.1" :value="$event->distance_km" required x-model.number="distance" />
                <x-admin.input name="elevation_gain_m" label="Elevation gain (m)" type="number" min="0" :value="$event->elevation_gain_m" required x-model.number="gain" />
                <x-admin.input name="elevation_loss_m" label="Elevation loss (m)" type="number" min="0" :value="$event->elevation_loss_m" required x-model.number="loss" />
                <x-admin.input name="duration_hours" label="Duration (hours)" type="number" step="0.5" min="0.5" :value="$event->duration_hours" />
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3 rounded-md bg-slate-50 px-4 py-3 text-sm">
                <span class="text-slate-500">Calculated difficulty:</span>
                <span class="text-lg font-bold" x-text="score"></span>
                <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="levelClasses" x-text="level.label"></span>
                <span class="text-xs text-slate-400">Updated automatically from distance and elevation.</span>
            </div>

            <div class="mt-4">
                <label for="gpx_file" class="block text-sm font-medium text-slate-700">GPX track</label>
                @if ($event->gpx_path)
                    <p class="mt-1 text-sm text-slate-600">
                        Current track: <a href="{{ $event->gpxUrl() }}" download class="text-emerald-700 hover:underline">download</a>
                        ({{ count($event->route_points ?? []) }} map points)
                    </p>
                @endif
                <input type="file" id="gpx_file" name="gpx_file" accept=".gpx"
                       class="mt-1 block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm">
                <p class="mt-1 text-xs text-slate-500">Optional. Hikers see the route on a map and can download the file. Max 5 MB.</p>
                @error('gpx_file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @if ($event->gpx_path)
                    <label class="mt-2 flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="remove_gpx" value="1" class="rounded border-slate-300"> Remove current track
                    </label>
                @endif
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold">Meeting points <span class="text-sm font-normal text-slate-500">(in pickup order, Lebanon time)</span></h2>
                <button type="button" @click="addPoint()" class="text-sm font-medium text-emerald-700 hover:underline"
                        x-show="meetingPoints.length < 10">+ Add meeting point</button>
            </div>

            <template x-if="meetingPoints.length === 0">
                <p class="text-sm text-amber-700">No meeting points yet. An upcoming event needs at least one before it can be published.</p>
            </template>

            <div class="space-y-2">
                <template x-for="(point, i) in meetingPoints" :key="point._key">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="w-6 text-sm text-slate-400" x-text="String(i + 1).padStart(2, '0')"></span>
                        <input type="text" :name="`meeting_points[${i}][name]`" x-model="point.name" placeholder="e.g. Beirut - Jnah"
                               maxlength="120" class="min-w-0 flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <input type="time" :name="`meeting_points[${i}][pickup_time]`" x-model="point.pickup_time"
                               class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <button type="button" @click="move(i, -1)" :disabled="i === 0" class="px-1 text-slate-500 disabled:opacity-30" aria-label="Move up">↑</button>
                        <button type="button" @click="move(i, 1)" :disabled="i === meetingPoints.length - 1" class="px-1 text-slate-500 disabled:opacity-30" aria-label="Move down">↓</button>
                        <button type="button" @click="meetingPoints.splice(i, 1)" class="px-1 text-red-600" aria-label="Remove">✕</button>
                    </div>
                </template>
            </div>
            @if ($errors->has('meeting_points') || $errors->has('meeting_points.*'))
                <ul class="mt-2 text-xs text-red-600">
                    @foreach (array_unique(array_merge($errors->get('meeting_points'), ...array_values($errors->get('meeting_points.*')))) as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold">Details</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.textarea name="included" label="What's included" :value="implode(PHP_EOL, $event->included ?? [])" rows="5" hint="One item per line." />
                <x-admin.textarea name="what_to_bring" label="What to bring" :value="implode(PHP_EOL, $event->what_to_bring ?? [])" rows="5" hint="One item per line." />
                <div class="sm:col-span-2">
                    <x-admin.textarea name="itinerary" label="Itinerary" :value="implode(PHP_EOL, $event->itinerary ?? [])" rows="6"
                                      hint="One step per line, in order. Start a line with a time to show it on the timeline, e.g. 08:30 Start of the hike" />
                </div>
            </div>
        </section>
    </div>

    {{-- Side column --}}
    <div class="space-y-6">
        <section class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold">Booking</h2>
            <div class="space-y-4">
                <x-admin.input name="fee" label="Fee (USD)" type="number" step="0.01" min="0" :value="$event->exists || $event->fee_cents ? $event->feeAmount() : ''" required hint="0 = free" />
                <x-admin.input name="capacity" label="Seats (capacity)" type="number" min="1" :value="$event->capacity" required />
                <label class="flex items-start gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_xtreme" value="1" class="mt-0.5 rounded border-slate-300" @checked(old('is_xtreme', $event->is_xtreme))>
                    <span>X event <span class="block text-xs text-slate-500">Only approved hikers can register.</span></span>
                </label>
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow-sm" x-data="{ preview: @js($event->coverImageUrl()), remove: false }">
            <h2 class="mb-4 font-semibold">Cover image</h2>
            <template x-if="preview && !remove">
                <img :src="preview" alt="Cover preview" class="mb-3 aspect-video w-full rounded object-cover">
            </template>
            <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp"
                   @change="const f = $event.target.files[0]; if (f) { preview = URL.createObjectURL(f); remove = false }"
                   class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm">
            <p class="mt-1 text-xs text-slate-500">JPG, PNG or WebP, at least 800×400, max 5 MB.</p>
            @error('cover_image') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            <div class="mt-4">
                <x-admin.input name="photo_credit" label="Photo credit" :value="$event->photo_credit" maxlength="120"
                               hint="Shown under the photo on the event page, e.g. a photographer's name." />
            </div>
            @if ($event->cover_image_path)
                <label class="mt-3 flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="remove_cover_image" value="1" x-model="remove" class="rounded border-slate-300"> Remove current image
                </label>
            @endif
        </section>
    </div>
</div>
