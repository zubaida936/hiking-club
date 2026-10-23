<x-admin.layout title="Events">
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <form method="GET" class="flex flex-wrap items-end gap-2">
            <input name="q" value="{{ request('q') }}" placeholder="Search title or location"
                   class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <select name="when" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">All dates</option>
                <option value="upcoming" @selected(request('when') === 'upcoming')>Upcoming</option>
                <option value="past" @selected(request('when') === 'past')>Past</option>
            </select>
            <select name="status" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <select name="level" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">All levels</option>
                @foreach ($levels as $level)
                    <option value="{{ $level->value }}" @selected((int) request('level') === $level->value)>{{ $level->label() }}</option>
                @endforeach
            </select>
            <x-admin.button variant="secondary">Filter</x-admin.button>
        </form>
        <x-admin.button :href="route('admin.events.create')" class="ml-auto">+ New event</x-admin.button>
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Event</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Distance</th>
                    <th class="px-4 py-3">Level</th>
                    <th class="px-4 py-3">Fee</th>
                    <th class="px-4 py-3">Seats</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($events as $event)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if ($event->coverImageUrl())
                                    <img src="{{ $event->coverImageUrl() }}" alt="" class="size-10 rounded object-cover">
                                @else
                                    <div class="size-10 rounded bg-slate-200"></div>
                                @endif
                                <div>
                                    <a href="{{ route('admin.events.edit', $event) }}" class="font-medium hover:underline">{{ $event->title }}</a>
                                    <div class="text-xs text-slate-500">
                                        {{ $event->location }}
                                        @if ($event->is_xtreme) · <span class="font-semibold text-red-600">X event</span> @endif
                                        @if ($event->meeting_points_count === 0) · <span class="text-amber-600">no meeting points</span> @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ $event->starts_on->format('D, M d, Y') }}
                            @unless ($event->isUpcoming()) <div class="text-xs text-slate-400">past</div> @endunless
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $event->distance_km }} km</td>
                        <td class="px-4 py-3">
                            <x-admin.badge :classes="$event->difficulty_level->badgeClasses()">{{ $event->difficulty_level->label() }}</x-admin.badge>
                            <div class="text-xs text-slate-500">score {{ $event->effort_score }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $event->formattedFee() }}</td>
                        <td class="px-4 py-3 whitespace-nowrap"><a href="{{ route('admin.events.registrations', $event) }}" class="hover:underline">{{ $event->seatsTaken() }} / {{ $event->capacity }}</a></td>
                        <td class="px-4 py-3"><x-admin.badge :classes="$event->status->badgeClasses()">{{ $event->status->label() }}</x-admin.badge></td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.events.edit', $event) }}" class="font-medium text-emerald-700 hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">No events found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $events->links() }}</div>
</x-admin.layout>
