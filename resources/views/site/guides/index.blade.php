<x-site.layout title="Guides" description="Hiking guides from our team: trails, peaks, campsites, how much water to carry, elevation versus distance and how our difficulty levels work."
               :noindex="$articles->currentPage() > 1">
    <section class="bg-brand-800 px-4 py-16 text-center text-white sm:px-6">
        <p class="font-display text-sm font-semibold uppercase tracking-[0.2em] text-accent-400">Hiker's Guide</p>
        <h1 class="mt-2 font-display text-4xl font-bold uppercase tracking-wide sm:text-5xl">Guides</h1>
        <p class="mx-auto mt-4 max-w-2xl text-white/80">Trails, peaks, campsites and practical tips from our guides, so you arrive prepared and enjoy every step.</p>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6">
        @if ($articles->isNotEmpty())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($articles as $article)
                    <x-site.article-card :article="$article" />
                @endforeach
            </div>
            <div class="mt-10">{{ $articles->links() }}</div>
        @else
            <div class="mx-auto max-w-xl rounded-2xl bg-brand-50 p-10 text-center">
                <p class="text-lg text-slate-600">Our first guides are being written. Check back soon!</p>
                <a href="{{ route('guide.difficulty') }}" class="mt-6 inline-block font-display font-semibold uppercase tracking-wide text-brand-600 hover:text-brand-800">Try the difficulty calculator &rarr;</a>
            </div>
        @endif
    </section>
</x-site.layout>
