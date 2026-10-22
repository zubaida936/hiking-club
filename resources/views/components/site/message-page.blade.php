@props(['title'])

{{-- Simple centered page for short outcomes (confirmations, unsubscribes, ...). --}}
<x-site.layout :title="$title" :noindex="true">
    <section class="bg-brand-800 py-16 text-center text-white">
        <h1 class="font-display text-3xl font-bold uppercase tracking-wide sm:text-4xl">{{ $title }}</h1>
    </section>
    <section class="mx-auto max-w-xl px-4 py-14 text-center text-lg text-slate-600">
        {{ $slot }}
    </section>
</x-site.layout>
