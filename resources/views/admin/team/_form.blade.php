<div class="grid gap-6 lg:grid-cols-3">
    <section class="space-y-4 rounded-lg bg-white p-6 shadow-sm lg:col-span-2">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-admin.input name="name" label="Name" :value="$member->name" required maxlength="80" />
            <x-admin.input name="role" label="Role" :value="$member->role" required maxlength="80" placeholder="Founder & lead guide" />
        </div>
        <x-admin.textarea name="bio" label="Short bio" :value="$member->bio" rows="4" maxlength="1000" />
    </section>

    <div class="space-y-6">
        <section class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
            <x-admin.input name="position" label="Order" type="number" min="0" max="1000" :value="$member->position" required hint="Lower numbers come first." />
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" @checked(old('is_active', $member->is_active))> Show on the About page
            </label>
        </section>

        <section class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold">Photo</h2>
            @if ($member->photoUrl())
                <img src="{{ $member->photoUrl() }}" alt="" class="mb-2 size-32 rounded-full object-cover">
                <label class="mb-4 flex items-center gap-2 text-xs text-slate-600">
                    <input type="checkbox" name="remove_photo" value="1" class="rounded border-slate-300"> Remove photo
                </label>
            @endif
            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                   class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm">
            <p class="mt-1 text-xs text-slate-500">Square portrait works best, max 10 MB.</p>
            @error('photo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </section>
    </div>
</div>
