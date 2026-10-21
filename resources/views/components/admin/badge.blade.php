@props(['classes' => 'bg-slate-200 text-slate-700'])

<span {{ $attributes->class(['inline-flex items-center whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium', $classes]) }}>{{ $slot }}</span>
