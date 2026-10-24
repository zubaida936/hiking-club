<x-admin.layout title="Guide articles">
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <form method="GET" class="flex flex-wrap items-end gap-2">
            <input name="q" value="{{ request('q') }}" placeholder="Search title or author" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <select name="status" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">All articles</option>
                @foreach (['published' => 'Published', 'scheduled' => 'Scheduled', 'draft' => 'Drafts'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-admin.button variant="secondary">Filter</x-admin.button>
        </form>
        <div class="ml-auto">
            <x-admin.button :href="route('admin.articles.create')">+ New article</x-admin.button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Article</th>
                    <th class="px-4 py-3">Author</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($articles as $article)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if ($article->coverUrl())
                                    <img src="{{ $article->coverUrl() }}" alt="" class="size-10 rounded object-cover">
                                @else
                                    <div class="size-10 rounded bg-slate-200"></div>
                                @endif
                                <a href="{{ route('admin.articles.edit', $article) }}" class="font-medium hover:underline">{{ $article->title }}</a>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ $article->author_name }}</td>
                        <td class="px-4 py-3">
                            @if ($article->isPublished())
                                <x-admin.badge classes="bg-emerald-100 text-emerald-800">Published {{ $article->published_at->setTimezone(config('site.timezone'))->format('j M Y') }}</x-admin.badge>
                            @elseif ($article->isScheduled())
                                <x-admin.badge classes="bg-sky-100 text-sky-800">Scheduled {{ $article->published_at->setTimezone(config('site.timezone'))->format('j M Y') }}</x-admin.badge>
                            @else
                                <x-admin.badge>Draft</x-admin.badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.articles.edit', $article) }}" class="font-medium text-emerald-700 hover:underline">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">No articles yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $articles->links() }}</div>
</x-admin.layout>
