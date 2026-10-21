<div class="grid gap-4 sm:grid-cols-2">
    <x-admin.input name="name" label="Full name" :value="$user->name" required />
    <x-admin.input name="email" label="Email (login)" type="email" :value="$user->email" required />
    <x-admin.input name="phone" label="Phone" :value="$user->phone" hint="e.g. +961 70 123 456" />

    <div>
        <label for="role" class="block text-sm font-medium text-slate-700">Role</label>
        <select id="role" name="role" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @foreach ($roles as $role)
                <option value="{{ $role->value }}" @selected(old('role', $user->primaryRole()?->value) === $role->value)>{{ $role->label() }}</option>
            @endforeach
        </select>
        @error('role') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <x-admin.input name="password" label="Password" type="password" autocomplete="new-password"
                   :hint="$user->exists ? 'Leave empty to keep the current password.' : 'Min 10 characters, letters and numbers.'" />
    <x-admin.input name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" />

    <label class="flex items-center gap-2 text-sm text-slate-700 sm:col-span-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" @checked(old('is_active', $user->is_active))>
        Active (can sign in)
    </label>
</div>
