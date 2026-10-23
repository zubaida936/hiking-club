@php
    use App\Domain\XEvents\Enums\XApplicationStatus;

    $a = $application;
    $row = fn (string $label, ?string $value) => $value === null || $value === '' ? '' : '<div class="flex justify-between gap-4 py-2"><dt class="text-slate-500">'.e($label).'</dt><dd class="text-right">'.e($value).'</dd></div>';
@endphp

<x-admin.layout :title="'X application · '.$a->name">
    <x-admin.member-link />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-lg bg-white p-6 shadow-sm">
                <h2 class="mb-2 font-semibold">About</h2>
                <dl class="divide-y divide-slate-100 text-sm">
                    {!! $row('Name', $a->name) !!}
                    {!! $row('WhatsApp', $a->phone) !!}
                    {!! $row('Email', $a->email) !!}
                    {!! $row('Age', (string) $a->age.($a->isMinor() ? ' (minor)' : '')) !!}
                    {!! $row('City', $a->city) !!}
                    {!! $row('Parent / guardian', $a->guardian_name ? $a->guardian_name.' · '.$a->guardian_phone : null) !!}
                </dl>
            </section>

            <section class="rounded-lg bg-white p-6 shadow-sm">
                <h2 class="mb-2 font-semibold">Experience</h2>
                <dl class="divide-y divide-slate-100 text-sm">
                    {!! $row('Hikes', implode(', ', $a->labels('frequency'))) !!}
                    {!! $row('Hardest hike', $a->hardest_route.($a->hardest_date ? ' ('.$a->hardest_date->format('M Y').')' : '')) !!}
                    {!! $row('… distance / climb / time', $a->hardest_km.' km · +'.$a->hardest_elevation_m.' m · '.$a->hardest_duration_hours.' h') !!}
                    {!! $row('Longest day', $a->longest_km.' km') !!}
                    {!! $row('Biggest ascent', '+'.$a->max_ascent_m.' m') !!}
                    {!! $row('Pace', implode(', ', $a->labels('pace'))) !!}
                    {!! $row('Steady 8 h+', $a->steady_pace ? 'Yes' : 'No / not sure') !!}
                    {!! $row('Has done', implode(', ', $a->labels('experience')) ?: 'None of the listed') !!}
                </dl>
                <p class="mt-3 text-sm"><span class="text-slate-500">Main challenge:</span> {{ $a->main_challenge }}</p>
                @if ($a->track_link)
                    <p class="mt-2 text-sm"><a href="{{ $a->track_link }}" target="_blank" rel="noopener noreferrer" class="text-emerald-700 hover:underline">Strava / Wikiloc ↗</a></p>
                @endif
            </section>

            <section class="rounded-lg bg-white p-6 shadow-sm">
                <h2 class="mb-2 font-semibold">Gear and health</h2>
                <p class="text-sm">{{ implode(', ', $a->labels('gear')) ?: 'No gear ticked' }}</p>
                @php
                    $missing = array_diff(array_keys((array) config('xevents.gear')), $a->gear ?? []);
                @endphp
                @if ($missing)
                    <p class="mt-1 text-xs text-amber-700">Missing: {{ implode(', ', array_map(fn ($k) => config("xevents.gear.$k"), $missing)) }}</p>
                @endif
                <p class="mt-3 text-sm"><span class="text-slate-500">Limitations:</span> {{ $a->limitations ?: 'None mentioned' }}</p>
            </section>
        </div>

        <div class="space-y-6">
            <section class="rounded-lg bg-white p-6 shadow-sm">
                <x-admin.badge :classes="$a->status->badgeClasses()">{{ $a->status->label() }}</x-admin.badge>
                <p class="mt-2 text-xs text-slate-500">Sent {{ $a->created_at->setTimezone(config('site.timezone'))->format('M d, Y H:i') }}</p>
                @if ($a->reviewed_at)
                    <p class="text-xs text-slate-500">Reviewed {{ $a->reviewed_at->diffForHumans() }}{{ $a->reviewer ? ' by '.$a->reviewer->name : '' }}{{ $a->decision_note ? ': '.$a->decision_note : '' }}</p>
                @endif
                @if ($a->access_sent_at)
                    <p class="text-xs text-slate-500">Access link sent {{ $a->access_sent_at->diffForHumans() }}</p>
                @endif

                <p class="mt-4 text-sm"><span class="text-slate-500">With us:</span> {{ $hiker->tier()->label() }}, {{ $hiker->totalPoints() }} points, {{ rtrim(rtrim(number_format($hiker->totalDistanceKm(), 1), '0'), '.') }} km hiked</p>
                @can('viewAny', \App\Models\Hiker::class)
                    <a href="{{ route('admin.points.show', $hiker) }}" class="text-xs text-emerald-700 hover:underline">Hiker profile &rarr;</a>
                @endcan

                @if ($a->status === XApplicationStatus::Pending)
                    <form method="POST" class="mt-4 space-y-3" x-data>
                        @csrf
                        <x-admin.textarea name="decision_note" label="Note for the decision (optional)" rows="2" maxlength="500" />
                        <div class="flex gap-2">
                            <x-admin.button formaction="{{ route('admin.x-applications.approve', $a) }}">Approve</x-admin.button>
                            <x-admin.button variant="danger" formaction="{{ route('admin.x-applications.reject', $a) }}">Reject</x-admin.button>
                        </div>
                    </form>
                @elseif ($a->status === XApplicationStatus::Approved)
                    <form method="POST" action="{{ route('admin.x-applications.access-link', $a) }}" class="mt-4">@csrf
                        <x-admin.button>Send access link</x-admin.button>
                    </form>
                    <form method="POST" action="{{ route('admin.x-applications.revoke', $a) }}" class="mt-3 space-y-2"
                          onsubmit="return confirm('Revoke X Events access? Their future X bookings will be refused.')">@csrf
                        <x-admin.input name="decision_note" label="Reason (optional)" maxlength="500" />
                        <x-admin.button variant="secondary">Revoke access</x-admin.button>
                    </form>
                @endif
            </section>

            <form method="POST" action="{{ route('admin.x-applications.note', $a) }}" class="space-y-3 rounded-lg bg-white p-6 shadow-sm">
                @csrf
                <x-admin.textarea name="internal_note" label="Internal notes (staff only)" :value="$a->internal_note" rows="5" />
                <x-admin.button variant="secondary">Save note</x-admin.button>
            </form>

            @if ($others->isNotEmpty())
                <section class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="mb-2 font-semibold">Other applications</h2>
                    <ul class="space-y-1 text-sm">
                        @foreach ($others as $other)
                            <li><a href="{{ route('admin.x-applications.show', $other) }}" class="hover:underline">{{ $other->created_at->format('M d, Y') }}</a> · {{ $other->status->label() }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>
</x-admin.layout>
