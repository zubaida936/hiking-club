<x-admin.layout title="Rewards used">
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <select name="reward" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">All rewards</option>
            @foreach ($catalog->all() as $reward)
                @continue(! $reward->isCountable())
                <option value="{{ $reward->key }}" @selected($selected === $reward->key)>{{ $reward->tier->label() }} · {{ $reward->label }}</option>
            @endforeach
        </select>
        <x-admin.button variant="secondary">Filter</x-admin.button>
    </form>

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Hiker</th>
                    <th class="px-4 py-3">Reward</th>
                    <th class="px-4 py-3">Used on</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($redemptions as $use)
                    @php($reward = $catalog->find($use->reward))
                    <tr class="align-top">
                        <td class="px-4 py-3 whitespace-nowrap text-slate-500">{{ $use->created_at->setTimezone(config('site.timezone'))->format('M d, Y') }}</td>
                        <td class="px-4 py-3"><a href="{{ route('admin.points.show', $use->hiker_id) }}" class="font-medium hover:underline">{{ $use->hiker->name }}</a></td>
                        <td class="px-4 py-3">{{ $reward?->label ?? $use->reward }}<span class="block text-xs text-slate-500">{{ $reward?->tier->label() }}</span></td>
                        <td class="px-4 py-3">
                            @if ($use->registration)
                                <a href="{{ route('admin.events.registrations', $use->registration->event_id) }}" class="hover:underline">{{ $use->registration->event->title }}</a>
                                <span class="block text-xs text-slate-500">{{ $use->registration->reference }} · −${{ number_format($use->discount_cents / 100, 2) }}</span>
                            @else
                                {{ $use->note ?? '—' }}
                                @if ($use->creator)<span class="block text-xs text-slate-500">by {{ $use->creator->name }}</span>@endif
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($use->released_at)
                                <x-admin.badge classes="bg-slate-100 text-slate-600">Given back (booking cancelled)</x-admin.badge>
                            @else
                                <x-admin.badge classes="bg-emerald-100 text-emerald-800">Used</x-admin.badge>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No rewards used yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $redemptions->links() }}</div>
</x-admin.layout>
