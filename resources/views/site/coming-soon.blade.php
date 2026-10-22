<x-site.layout :title="$title" :noindex="true">
    <section class="bg-brand-800 py-20 text-center text-white">
        <h1 class="font-display text-4xl font-bold uppercase tracking-wide sm:text-5xl">{{ $title }}</h1>
    </section>
    <section class="mx-auto max-w-2xl px-4 py-16 text-center">
        <p class="text-lg text-slate-600">This page is on its way. Check back soon!</p>
        <a href="{{ route('home') }}" class="mt-8 inline-block rounded-full bg-brand-600 px-6 py-3 font-display font-semibold uppercase tracking-wide text-white hover:bg-brand-700">
            Back to home
        </a>
    </section>
</x-site.layout>
