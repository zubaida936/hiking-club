{{-- Placeholder mark until the club logo is decided; swap the SVG for the real logo file. --}}
<a href="{{ route('home') }}" {{ $attributes->class('flex items-center gap-2 font-display text-lg font-bold uppercase tracking-wider') }}>
    <svg class="size-8 text-accent-500" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
        <path d="M2 27 12 9l5 9 3-5 10 14z"/>
    </svg>
    <span>{{ config('app.name') }}</span>
</a>
