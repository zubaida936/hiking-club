<div class="grid gap-6 lg:grid-cols-3">
    <section class="space-y-4 rounded-lg bg-white p-6 shadow-sm lg:col-span-2">
        <x-admin.input name="eyebrow" label="Small title" :value="$slide->eyebrow" maxlength="40" hint="Optional line above the heading, e.g. 🍂 Autumn" />
        <x-admin.input name="heading" label="Heading" :value="$slide->heading" required maxlength="120" />
        <x-admin.textarea name="text" label="Text" :value="$slide->text" rows="2" maxlength="300" />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-admin.input name="cta_label" label="Button text" :value="$slide->cta_label" maxlength="40" hint="Leave both empty for no button." />
            <x-admin.input name="cta_url" label="Button link" :value="$slide->cta_url" maxlength="255" placeholder="/events" hint="A page of this site (/events, /shop) or a full https:// link." />
        </div>
    </section>

    <div class="space-y-6">
        <section class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
            <x-admin.input name="position" label="Order" type="number" min="0" max="1000" :value="$slide->position" required hint="Lower numbers come first." />
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" @checked(old('is_active', $slide->is_active))> Show on the homepage
            </label>
        </section>

        <section class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold">Background photo</h2>
            @if ($slide->imageUrl())
                <img src="{{ $slide->imageUrl() }}" alt="" class="mb-2 aspect-video w-full rounded object-cover">
                <label class="mb-4 flex items-center gap-2 text-xs text-slate-600">
                    <input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300"> Remove photo
                </label>
            @endif
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                   class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm">
            <p class="mt-1 text-xs text-slate-500">Wide landscape photo (16:9, ideally 1920 px wide), max 10 MB. It is darkened so the text stays readable. Without a photo the slide uses a coloured background.</p>
            @error('image') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </section>
    </div>
</div>
