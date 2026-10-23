<x-admin.layout title="Points">
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <input name="q" value="{{ request('q') }}" placeholder="Name or phone" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <x-admin.button variant="secondary">Search</x-admin.button>
    </form>

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Hiker</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">Hikes completed</th>
                    <th class="px-4 py-3">Points</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($hikers as $hiker)
                    <tr>
                        <td class="px-4 py-3"><a href="{{ route('admin.points.show', $hiker) }}" class="font-medium hover:underline">{{ $hiker->name }}</a></td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $hiker->phone }}</td>
                        <td class="px-4 py-3">{{ $hiker->hikes_completed }}</td>
                        <td class="px-4 py-3 font-semibold">{{ $hiker->totalPoints() }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.points.show', $hiker) }}" class="font-medium text-emerald-700 hover:underline">History</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No hikers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $hikers->links() }}</div>

    <p class="mt-4 text-xs text-slate-500">
        Points per completed hike: Level 1 {{ config('points.hike.1') }}, Level 2 {{ config('points.hike.2') }}, Level 3 {{ config('points.hike.3') }}, Level 4 {{ config('points.hike.4') }}
        · +{{ config('points.early_booking.points') }} for booking {{ config('points.early_booking.days') }}+ days ahead
        · +{{ config('points.referral') }} for bringing a new hiker
        @if (config('points.paid_only')) · paid hikes only (free hikes always count) @endif
    </p>
</x-admin.layout>
