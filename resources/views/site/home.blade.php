{{-- Temporary hero; the full homepage is its own ClickUp task. --}}
<x-site.layout>
    <section class="bg-brand-800 px-4 py-28 text-center text-white">
        <h1 class="font-display text-4xl font-bold uppercase tracking-wide sm:text-6xl">{{ config('app.name') }}</h1>
        <p class="mx-auto mt-4 max-w-xl text-lg text-white/80">Trails, summits and good company, every weekend.</p>
        <a href="{{ route('events.index') }}" class="mt-10 inline-block rounded-full bg-accent-500 px-8 py-3 font-display font-bold uppercase tracking-wide hover:bg-accent-600">
            See upcoming events
        </a>
    </section>
</x-site.layout>
