<x-admin.layout :title="$page->title">
    <form method="POST" action="{{ route('admin.legal-pages.update', $page->document) }}">
        @csrf @method('PUT')

        <section class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
            <x-admin.input name="title" label="Title" :value="$page->title" required maxlength="160" />
            <x-admin.textarea name="body" label="Text" :value="$page->body" rows="24"
                              hint="Markdown: ## Heading, **bold**, *italic*, - list item, [link text](https://…). Leave an empty line between paragraphs. While empty, the page tells visitors it is being prepared." />
            @if ($page->exists)
                <p class="text-xs text-slate-500">Last updated {{ $page->updated_at?->setTimezone(config('site.timezone'))->format('j F Y, H:i') }}. Saving shows today's date on the site.</p>
            @endif
        </section>

        <div class="mt-6 flex flex-wrap gap-2">
            <x-admin.button>Save page</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.legal-pages.index')">Back to legal pages</x-admin.button>
            <x-admin.button variant="secondary" :href="route($page->document->routeName())" target="_blank">View on the site</x-admin.button>
        </div>
    </form>
</x-admin.layout>
