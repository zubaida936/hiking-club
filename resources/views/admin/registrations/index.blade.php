<x-admin.layout title="Registrations">
    <div class="mb-4 flex gap-2 text-sm">
        <a href="{{ route('admin.registrations.index') }}"
           @class(['rounded-md px-3 py-1.5 font-medium', 'bg-slate-800 text-white' => ! $past, 'bg-white text-slate-700 shadow-sm hover:bg-slate-50' => $past])>Upcoming</a>
        <a href="{{ route('admin.registrations.index', ['when' => 'past']) }}"
           @class(['rounded-md px-3 py-1.5 font-medium', 'bg-slate-800 text-white' => $past, 'bg-white text-slate-700 shadow-sm hover:bg-slate-50' => ! $past])>Past</a>
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Event</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Seats taken</th>
                    <th class="px-4 py-3">Awaiting approval</th>
                    <th class="px-4 py-3">Waitlist</th>
                    <th class="px-4 py-3">Paid</th>
                    <th class="px-4 py-3">Attended</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($events as $event)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.events.registrations', $event) }}" class="font-medium hover:underline">{{ $event->title }}</a>
                            <div class="text-xs text-slate-500">
                                {{ $event->location }}
                                @if ($event->is_xtreme) · <span class="font-semibold text-red-600">X event</span> @endif
                                @unless ($event->isPublished()) · <span class="text-slate-400">{{ $event->status->label() }}</span> @endunless
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $event->starts_on->format('D, M d, Y') }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span @class(['font-semibold', 'text-red-600' => $event->isSoldOut()])>{{ $event->seatsTaken() }} / {{ $event->capacity }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $event->pending_count ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $event->waitlisted_count ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $event->fee_cents === 0 ? 'Free' : $event->paid_count.' / '.$event->seatsTaken() }}</td>
                        <td class="px-4 py-3">{{ $event->hikeDayReached() ? $event->attended_count : '—' }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.events.registrations', $event) }}" class="font-medium text-emerald-700 hover:underline">View list</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">No {{ $past ? 'past' : 'upcoming' }} events.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $events->links() }}</div>
</x-admin.layout>
