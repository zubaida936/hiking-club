@props(['eyebrow' => null, 'dark' => false])

<div {{ $attributes->class('text-center') }}>
    @if ($eyebrow)
        <p @class(['font-display text-sm font-semibold uppercase tracking-[0.2em]', 'text-accent-400' => $dark, 'text-accent-600' => ! $dark])>{{ $eyebrow }}</p>
    @endif
    <h2 @class(['mt-2 font-display text-3xl font-bold uppercase tracking-wide sm:text-4xl', 'text-white' => $dark, 'text-brand-900' => ! $dark])>
        {{ $slot }}
    </h2>
</div>
