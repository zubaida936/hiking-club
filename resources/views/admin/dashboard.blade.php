<x-admin.layout title="Dashboard">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($stats as $label => $value)
            <div class="rounded-lg bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-3xl font-bold">{{ number_format($value) }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 rounded-lg bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Next challenge</h2>
        @if ($nextEvent)
            <p class="mt-2 text-lg font-semibold">{{ $nextEvent->title }}</p>
            <p class="text-sm text-slate-600">
                {{ $nextEvent->starts_on->format('D, M d, Y') }} · {{ $nextEvent->distance_km }} km ·
                {{ $nextEvent->difficulty_level->label() }}
            </p>
            @can('update', $nextEvent)
                <a href="{{ route('admin.events.edit', $nextEvent) }}" class="mt-2 inline-block text-sm font-medium text-emerald-700 hover:underline">Open event →</a>
            @endcan
        @else
            <p class="mt-2 text-sm text-slate-500">No upcoming published event.</p>
        @endif
    </div>
</x-admin.layout>
