<x-admin.layout title="X applications">
    <div class="mb-4 flex flex-wrap items-center gap-2 text-sm">
        <a href="{{ route('admin.x-applications.index') }}"
           @class(['rounded-md px-3 py-1.5 font-medium', 'bg-slate-800 text-white' => ! request('status'), 'bg-white text-slate-700 shadow-sm hover:bg-slate-50' => request('status')])>All</a>
        @foreach ($statuses as $status)
            <a href="{{ route('admin.x-applications.index', ['status' => $status->value]) }}"
               @class(['rounded-md px-3 py-1.5 font-medium', 'bg-slate-800 text-white' => request('status') === $status->value, 'bg-white text-slate-700 shadow-sm hover:bg-slate-50' => request('status') !== $status->value])>
                {{ $status->label() }}@if ($status === \App\Domain\XEvents\Enums\XApplicationStatus::Pending && $pendingCount) ({{ $pendingCount }})@endif
            </a>
        @endforeach
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Applicant</th>
                    <th class="px-4 py-3">Hardest hike</th>
                    <th class="px-4 py-3">Longest / ascent</th>
                    <th class="px-4 py-3">Sent</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($applications as $application)
                    <tr class="align-top">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.x-applications.show', $application) }}" class="font-medium hover:underline">{{ $application->name }}</a>
                            <span class="block text-xs text-slate-500">{{ $application->age }} · {{ $application->city }} · {{ $application->phone }}</span>
                        </td>
                        <td class="px-4 py-3">
                            {{ $application->hardest_route }}
                            <span class="block text-xs text-slate-500">{{ $application->hardest_km }} km · +{{ $application->hardest_elevation_m }} m · {{ $application->hardest_duration_hours }} h</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $application->longest_km }} km · +{{ $application->max_ascent_m }} m</td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-500">{{ $application->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-3"><x-admin.badge :classes="$application->status->badgeClasses()">{{ $application->status->label() }}</x-admin.badge></td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.x-applications.show', $application) }}" class="font-medium text-emerald-700 hover:underline">Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No applications.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $applications->links() }}</div>
</x-admin.layout>
