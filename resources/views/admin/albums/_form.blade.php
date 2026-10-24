<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <section class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold">Photos</h2>
            @if ($album->exists && $album->photos->isNotEmpty())
                <p class="mb-3 text-xs text-slate-500">The first photo is the album cover.</p>
                <div class="mb-4 grid grid-cols-3 gap-3 sm:grid-cols-4">
                    @foreach ($album->photos as $photo)
                        <div>
                            <img src="{{ $photo->url() }}" alt="" loading="lazy" @class(['aspect-square w-full rounded object-cover', 'ring-2 ring-emerald-500' => $loop->first])>
                            <label class="mt-1 flex items-center gap-1 text-xs text-slate-600">
                                <input type="radio" name="cover_photo" value="{{ $photo->id }}" class="border-slate-300" @checked($loop->first)> Cover
                            </label>
                            <label class="flex items-center gap-1 text-xs text-slate-600">
                                <input type="checkbox" name="remove_photos[]" value="{{ $photo->id }}" class="rounded border-slate-300"> Remove
                            </label>
                        </div>
                    @endforeach
                </div>
            @endif
            <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp"
                   class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm">
            <p class="mt-1 text-xs text-slate-500">
                Up to {{ \App\Http\Requests\Admin\AlbumRequest::MAX_PHOTOS_PER_UPLOAD }} photos and 25 MB per upload (JPG, PNG or WebP, max 10 MB each).
                For more, save and upload the next batch. Photos are resized for the web.
            </p>
            @error('photos') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('photos.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </section>
    </div>

    <section class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
        <h2 class="font-semibold">Album</h2>
        <x-admin.input name="title" label="Title" :value="$album->title" required maxlength="160" />
        <div>
            <label for="event_id" class="block text-sm font-medium text-slate-700">Hike</label>
            <select id="event_id" name="event_id" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">Not linked to a hike</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected((int) old('event_id', $album->event_id) === $event->id)>{{ $event->starts_on->format('j M Y') }} · {{ $event->title }}</option>
                @endforeach
            </select>
            @error('event_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <x-admin.input name="taken_on" label="Date" type="date" :value="$album->taken_on?->format('Y-m-d')" />
        <x-admin.textarea name="description" label="Description" :value="$album->description" rows="4" maxlength="2000" />
        <x-admin.input name="slug" label="URL slug" :value="$album->slug" maxlength="180" hint="Leave empty to generate from the title." />
        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="is_published" value="1" class="rounded border-slate-300" @checked(old('is_published', $album->is_published))> Show in the gallery
        </label>
    </section>
</div>
