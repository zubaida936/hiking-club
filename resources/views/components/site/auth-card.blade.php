@props(['title', 'intro' => null])

{{-- Narrow card for the member login, activation and password pages. --}}
<x-site.layout :title="$title" :noindex="true">
    <section class="bg-brand-800 px-4 py-12 text-center text-white sm:px-6">
        <h1 class="font-display text-3xl font-bold uppercase tracking-wide sm:text-4xl">{{ $title }}</h1>
        @if ($intro)
            <p class="mx-auto mt-3 max-w-md text-white/85">{{ $intro }}</p>
        @endif
    </section>

    <div class="mx-auto max-w-md px-4 py-12 sm:px-6">
        @if (session('error'))
            <p class="mb-6 rounded-xl bg-red-50 px-5 py-4 font-medium text-red-800" role="alert">{{ session('error') }}</p>
        @endif

        {{ $slot }}
    </div>
</x-site.layout>
