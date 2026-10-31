{{-- An empty page is not indexed: it only says the text is on its way. --}}
<x-site.layout :title="$page->title" :noindex="! $page->hasContent()">
    <section class="bg-brand-800 px-4 py-16 text-center text-white sm:px-6">
        <h1 class="font-display text-4xl font-bold uppercase tracking-wide sm:text-5xl">{{ $page->title }}</h1>
        @if ($page->hasContent() && $page->updated_at)
            <p class="mt-4 text-white/80">
                Last updated <time datetime="{{ $page->updated_at->toDateString() }}">{{ $page->updated_at->setTimezone(config('site.timezone'))->format('j F Y') }}</time>
            </p>
        @endif
    </section>

    @if ($page->hasContent())
        <div class="article-body mx-auto max-w-3xl px-4 py-14 sm:px-6">
            {{ $page->bodyHtml() }}
        </div>
    @else
        <section class="mx-auto max-w-2xl px-4 py-16 text-center">
            <p class="text-lg text-slate-600">We are preparing this page. Questions in the meantime? Email us at
                <a href="mailto:{{ config('site.contact.email') }}" class="text-brand-700 underline">{{ config('site.contact.email') }}</a>.</p>
        </section>
    @endif
</x-site.layout>
