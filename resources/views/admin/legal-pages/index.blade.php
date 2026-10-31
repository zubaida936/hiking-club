<x-admin.layout title="Legal pages">
    <p class="mb-4 text-sm text-slate-600">Linked from the site footer and from the consent checkboxes on the booking, checkout and X Events forms.</p>

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Page</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Last updated</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($pages as $page)
                    <tr>
                        <td class="px-4 py-3 font-medium">
                            <a href="{{ route('admin.legal-pages.edit', $page->document) }}" class="hover:text-emerald-700">{{ $page->title }}</a>
                        </td>
                        <td class="px-4 py-3">
                            @if ($page->hasContent())
                                <x-admin.badge classes="bg-emerald-100 text-emerald-800">Published</x-admin.badge>
                            @else
                                <x-admin.badge classes="bg-amber-100 text-amber-800">No text yet</x-admin.badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ $page->exists ? $page->updated_at?->setTimezone(config('site.timezone'))->format('j M Y, H:i') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.legal-pages.edit', $page->document) }}" class="font-medium text-emerald-700 hover:underline">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin.layout>
