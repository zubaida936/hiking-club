<x-admin.layout title="Homepage slides">
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <p class="text-sm text-slate-600">
            The carousel at the top of the homepage, in this order. It changes slide every 6 seconds.
            @if ($slides->where('is_active', true)->isEmpty())
                <strong class="text-amber-700">No active slide yet, so the homepage shows the 3 placeholder slides.</strong>
            @endif
        </p>
        <div class="ml-auto flex gap-2">
            <x-admin.button variant="secondary" :href="route('home')" target="_blank">View homepage</x-admin.button>
            <x-admin.button :href="route('admin.hero-slides.create')">+ New slide</x-admin.button>
        </div>
    </div>

    <div class="space-y-3">
        @forelse ($slides as $slide)
            <div class="flex flex-wrap items-center gap-4 rounded-lg bg-white p-4 shadow-sm">
                <div class="relative h-20 w-36 shrink-0 overflow-hidden rounded bg-linear-to-br from-emerald-950 via-emerald-800 to-amber-600">
                    @if ($slide->imageUrl())
                        <img src="{{ $slide->imageUrl() }}" alt="" class="absolute inset-0 size-full object-cover">
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    @if ($slide->eyebrow)<p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $slide->eyebrow }}</p>@endif
                    <p class="font-medium">{{ $slide->heading }}</p>
                    @if ($slide->cta_label)<p class="truncate text-xs text-slate-500">Button: {{ $slide->cta_label }} → {{ $slide->cta_url }}</p>@endif
                </div>
                <span class="text-xs text-slate-500">Order {{ $slide->position }}</span>
                <x-admin.badge :classes="$slide->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700'">{{ $slide->is_active ? 'Shown' : 'Hidden' }}</x-admin.badge>
                <a href="{{ route('admin.hero-slides.edit', $slide) }}" class="text-sm font-medium text-emerald-700 hover:underline">Edit</a>
            </div>
        @empty
            <p class="rounded-lg bg-white p-8 text-center text-slate-500 shadow-sm">No slides yet.</p>
        @endforelse
    </div>
</x-admin.layout>
