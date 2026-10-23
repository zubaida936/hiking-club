@props(['name' => 'password', 'label' => 'Password', 'hint' => null, 'autocomplete' => 'current-password'])

{{-- Password input with a show/hide toggle. --}}
<div x-data="{ show: false }">
    <label for="{{ $name }}" class="block text-sm font-semibold text-slate-700">{{ $label }}</label>
    <div class="relative mt-1">
        <input id="{{ $name }}" name="{{ $name }}" type="password" :type="show ? 'text' : 'password'" required autocomplete="{{ $autocomplete }}"
               @error($name) aria-invalid="true" aria-describedby="{{ $name }}-help" @enderror
               @class([
                   'block w-full rounded-xl border bg-white py-3 pl-4 pr-20 text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-500',
                   'border-red-400' => $errors->has($name),
                   'border-slate-300' => ! $errors->has($name),
               ])>
        <button type="button" @click="show = !show" :aria-pressed="show.toString()" aria-label="Show password"
                class="absolute inset-y-0 right-0 px-4 text-sm font-semibold text-brand-700 hover:text-brand-900">
            <span x-text="show ? 'Hide' : 'Show'">Show</span>
        </button>
    </div>
    @error($name)
        <p id="{{ $name }}-help" class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @else
        @if ($hint)
            <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
        @endif
    @enderror
</div>
