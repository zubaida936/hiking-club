<x-admin.layout title="Team">
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <p class="text-sm text-slate-600">Guides and organizers shown on the About page, in this order.</p>
        <div class="ml-auto flex gap-2">
            <x-admin.button variant="secondary" :href="route('about')" target="_blank">View About page</x-admin.button>
            <x-admin.button :href="route('admin.team.create')">+ New team member</x-admin.button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($members as $member)
            <a href="{{ route('admin.team.edit', $member) }}" class="flex items-center gap-3 rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200 hover:ring-emerald-500">
                @if ($member->photoUrl())
                    <img src="{{ $member->photoUrl() }}" alt="" class="size-14 shrink-0 rounded-full object-cover">
                @else
                    <div class="flex size-14 shrink-0 items-center justify-center rounded-full bg-slate-200 font-semibold text-slate-500">{{ mb_substr($member->name, 0, 1) }}</div>
                @endif
                <div class="min-w-0">
                    <p class="truncate font-medium">{{ $member->name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $member->role }}</p>
                    <p class="mt-1 text-xs text-slate-500">
                        Order {{ $member->position }}
                        @unless ($member->is_active) · <span class="font-medium text-slate-700">Hidden</span> @endunless
                    </p>
                </div>
            </a>
        @empty
            <p class="col-span-full rounded-lg bg-white p-8 text-center text-slate-500 shadow-sm">No team members yet.</p>
        @endforelse
    </div>
</x-admin.layout>
