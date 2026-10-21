@props(['variant' => 'primary', 'href' => null])

@php
    $classes = 'inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-semibold shadow-sm transition '.match ($variant) {
        'primary' => 'bg-emerald-600 text-white hover:bg-emerald-700',
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
        default => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit'])->class($classes) }}>{{ $slot }}</button>
@endif
