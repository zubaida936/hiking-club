@php
    $published = $article->published_at?->setTimezone(config('site.timezone'));

    // Structured data so search engines show the article with its author and date.
    $structured = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $article->title,
        'description' => $article->summary(160),
        'author' => ['@type' => 'Person', 'name' => $article->author_name],
        'publisher' => ['@type' => 'Organization', 'name' => config('app.name')],
        'datePublished' => $article->published_at?->toAtomString(),
        'dateModified' => $article->updated_at?->toAtomString(),
        'mainEntityOfPage' => route('guide.articles.show', $article->slug),
        'image' => $article->coverUrl() ?? asset(config('site.share_image')),
    ];
@endphp

@push('head')
    {{-- JSON_HEX_TAG escapes "<", so no value can close the script tag. --}}
    <script type="application/ld+json">{!! json_encode($structured, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

<x-site.layout :title="$article->title" :description="$article->summary(160)" :image="$article->coverUrl()" type="article" :noindex="$preview">
    @if ($preview)
        <div class="bg-amber-100 px-4 py-3 text-center text-sm font-medium text-amber-900">
            Preview: this article is {{ $article->isScheduled() ? 'scheduled for '.$published->format('j M Y') : 'a draft' }} and not visible to visitors yet.
        </div>
    @endif

    <article>
        <header class="relative isolate overflow-hidden bg-brand-900 text-white">
            @if ($article->coverUrl())
                <img src="{{ $article->coverUrl() }}" alt="" width="1600" height="1067" fetchpriority="high" class="absolute inset-0 -z-10 size-full object-cover">
                <div class="absolute inset-0 -z-10 bg-brand-900/65"></div>
            @endif
            <div class="mx-auto max-w-3xl px-4 py-20 text-center sm:px-6 sm:py-28">
                <a href="{{ route('guide.articles') }}" class="font-display text-sm font-semibold uppercase tracking-[0.2em] text-accent-400 hover:text-accent-500">Guides</a>
                <h1 class="mt-3 font-display text-4xl font-bold uppercase leading-tight tracking-wide sm:text-5xl">{{ $article->title }}</h1>
                <p class="mt-5 text-white/80">
                    By {{ $article->author_name }}
                    @if ($published) · <time datetime="{{ $article->published_at->toDateString() }}">{{ $published->format('j F Y') }}</time> @endif
                    · {{ $article->readingMinutes() }} min read
                </p>
            </div>
        </header>

        <div class="article-body mx-auto max-w-3xl px-4 py-14 sm:px-6">
            {{ $article->bodyHtml() }}
        </div>
    </article>

    @if ($more->isNotEmpty())
        <section class="bg-slate-50 py-16" aria-labelledby="more-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <x-site.section-heading eyebrow="Keep reading" class="mb-10"><span id="more-heading">More guides</span></x-site.section-heading>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($more as $other)
                        <x-site.article-card :article="$other" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-site.layout>
