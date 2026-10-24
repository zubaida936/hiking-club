@props(['article'])

<article {{ $attributes->class('flex flex-col overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-slate-200') }}>
    <a href="{{ route('guide.articles.show', $article->slug) }}" class="relative block aspect-3/2 bg-linear-to-br from-brand-800 to-brand-500" tabindex="-1" aria-hidden="true">
        @if ($article->coverUrl())
            <img src="{{ $article->coverUrl() }}" alt="" width="1200" height="800" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover">
        @else
            <svg class="absolute inset-x-0 bottom-0 h-2/3 w-full text-black/20" viewBox="0 0 400 200" preserveAspectRatio="none" fill="currentColor">
                <path d="M0 200 90 70l60 60 80-110 90 120 80-60v120z"/>
            </svg>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-5">
        <p class="text-sm text-slate-500">
            <time datetime="{{ $article->published_at?->toDateString() }}">{{ $article->published_at?->setTimezone(config('site.timezone'))->format('j M Y') }}</time>
            · {{ $article->author_name }}
        </p>
        <h3 class="mt-2 font-display text-xl font-bold uppercase tracking-wide text-brand-900">
            <a href="{{ route('guide.articles.show', $article->slug) }}" class="hover:text-brand-600">{{ $article->title }}</a>
        </h3>
        <p class="mt-2 flex-1 text-slate-600">{{ $article->summary(160) }}</p>
        <a href="{{ route('guide.articles.show', $article->slug) }}"
           class="mt-5 inline-block font-display text-sm font-bold uppercase tracking-wide text-accent-600 hover:text-accent-500">
            Read More<span class="sr-only">: {{ $article->title }}</span> &rarr;
        </a>
    </div>
</article>
