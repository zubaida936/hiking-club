@props(['hikers', 'highlight' => null])

{{-- Ranked hikers: position, public name, rank tier, hikes, km and points. $highlight = the viewer's hiker id. --}}
<ol {{ $attributes->class('divide-y divide-slate-100') }}>
    @foreach ($hikers as $i => $hiker)
        @php($tier = $hiker->tier())
        <li @class([
            'flex items-center gap-3 px-4 py-3 sm:gap-4 sm:px-6',
            'bg-accent-400/15' => $highlight !== null && $hiker->id === $highlight,
        ])>
            <span @class([
                'flex size-8 shrink-0 items-center justify-center rounded-full font-display font-bold',
                'bg-accent-500 text-white' => $i < 3,
                'text-brand-700' => $i >= 3,
            ])>{{ $i + 1 }}</span>
            <span class="min-w-0 flex-1">
                <span class="block truncate font-semibold text-brand-900">
                    {{ $hiker->publicName() }}
                    @if ($highlight !== null && $hiker->id === $highlight)<span class="text-xs font-normal text-slate-500">(you)</span>@endif
                </span>
                <span class="text-xs text-slate-500">
                    <span class="rounded-full px-2 py-0.5 font-semibold {{ $tier->badgeClasses() }}">{{ $tier->label() }}</span>
                    · {{ $hiker->hikes_completed }} {{ Str::plural('hike', $hiker->hikes_completed) }}
                    · {{ rtrim(rtrim(number_format($hiker->totalDistanceKm(), 1), '0'), '.') }} km
                </span>
            </span>
            <span class="shrink-0 text-right font-display text-lg font-bold text-accent-600">
                {{ $hiker->totalPoints() }}<span class="block text-xs font-normal uppercase tracking-wide text-slate-500">points</span>
            </span>
        </li>
    @endforeach
</ol>
