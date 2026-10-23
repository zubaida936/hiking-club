@props(['name', 'label', 'type' => 'text', 'hint' => null, 'optional' => false])

{{-- Labelled public-site form input with its hint and validation error. --}}
<div>
    <label for="{{ $name }}" class="block text-sm font-semibold text-slate-700">
        {{ $label }} @if ($optional)<span class="font-normal text-slate-400">(optional)</span>@endif
    </label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name) }}"
           @if ($hint || $errors->has($name)) aria-describedby="{{ $name }}-help" @endif
           @error($name) aria-invalid="true" @enderror
           {{ $attributes->class([
               'mt-1 block w-full rounded-xl border bg-white px-4 py-3 text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500',
               'border-red-400' => $errors->has($name),
               'border-slate-300' => ! $errors->has($name),
           ]) }}>
    @error($name)
        <p id="{{ $name }}-help" class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @else
        @if ($hint)
            <p id="{{ $name }}-help" class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
        @endif
    @enderror
</div>
