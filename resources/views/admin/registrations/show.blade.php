@php
    use App\Domain\Identity\Enums\Permission;
    use App\Domain\Registrations\Enums\Attendance;

    $canManage = auth()->user()->can(Permission::ManageRegistrations->value);
    $canAttend = auth()->user()->can(Permission::ManageAttendance->value) && $event->hikeDayReached();
    $paidEvent = $event->fee_cents > 0;
@endphp

<x-admin.layout :title="'Registrations · '.$event->title">
    <div x-data="{ cancel: null, whatsapp: false, copied: false }">
        {{-- Event summary & tools --}}
        <div class="mb-6 flex flex-wrap items-center gap-x-6 gap-y-3 rounded-lg bg-white p-4 shadow-sm">
            <div>
                <p class="font-semibold">{{ $event->title }}</p>
                <p class="text-sm text-slate-500">
                    {{ $event->starts_on->format('l, j F Y') }} · {{ $event->formattedFee() }}
                    @if ($event->is_xtreme) · <span class="font-semibold text-red-600">X event</span> @endif
                </p>
            </div>
            <dl class="flex gap-6 text-sm">
                <div><dt class="text-xs uppercase text-slate-500">Seats taken</dt><dd @class(['font-semibold', 'text-red-600' => $event->isSoldOut()])>{{ $event->seatsTaken() }} / {{ $event->capacity }}</dd></div>
                <div><dt class="text-xs uppercase text-slate-500">Bookings close</dt><dd class="font-semibold">{{ $event->registrationDeadline()->setTimezone(config('site.timezone'))->format('D j M, H:i') }}</dd></div>
            </dl>
            <div class="ml-auto flex flex-wrap gap-2">
                <x-admin.button variant="secondary" type="button" @click="whatsapp = !whatsapp">WhatsApp list</x-admin.button>
                <x-admin.button variant="secondary" :href="route('admin.events.registrations.export', ['event' => $event] + request()->only(['status', 'pickup', 'q']))">Export CSV</x-admin.button>
                @can(Permission::ManageEvents->value)
                    <x-admin.button variant="secondary" :href="route('admin.events.edit', $event)">Event settings</x-admin.button>
                @endcan
            </div>
        </div>

        {{-- WhatsApp list (hikers holding a seat, by pickup point) --}}
        <div x-show="whatsapp" x-cloak class="mb-6 rounded-lg bg-white p-4 shadow-sm">
            <div class="mb-2 flex items-center justify-between">
                <h2 class="font-semibold">List for WhatsApp</h2>
                <x-admin.button variant="secondary" type="button"
                                @click="navigator.clipboard.writeText($refs.list.value); copied = true; setTimeout(() => copied = false, 2000)">
                    <span x-text="copied ? 'Copied!' : 'Copy'">Copy</span>
                </x-admin.button>
            </div>
            <textarea x-ref="list" readonly rows="12" class="w-full rounded-md border border-slate-300 bg-slate-50 p-3 font-mono text-sm">{{ $whatsapp }}</textarea>
        </div>

        {{-- Per pickup point (seat holders) --}}
        @if ($pickups->isNotEmpty())
            <div class="mb-4 flex flex-wrap gap-2 text-sm">
                @foreach ($pickups as $pickup)
                    @php($value = $pickup->pickup_point ?? '-')
                    <a href="{{ request()->fullUrlWithQuery(['pickup' => request('pickup') === $value ? null : $value]) }}"
                       @class(['rounded-full border px-3 py-1', 'border-emerald-600 bg-emerald-50 text-emerald-800' => request('pickup') === $value, 'border-slate-300 bg-white hover:bg-slate-50' => request('pickup') !== $value])>
                        {{ $pickup->pickup_point ?? 'No pickup point' }} {{ $pickup->pickup_time ? substr($pickup->pickup_time, 0, 5) : '' }}
                        <strong class="ml-1">{{ $pickup->total }}</strong>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Filters --}}
        <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
            @if (request('pickup'))<input type="hidden" name="pickup" value="{{ request('pickup') }}">@endif
            <input name="q" value="{{ request('q') }}" placeholder="Name, phone or reference" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <select name="status" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">All except cancelled</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <x-admin.button variant="secondary">Filter</x-admin.button>
            @if (request()->hasAny(['q', 'status', 'pickup']))
                <a href="{{ route('admin.events.registrations', $event) }}" class="px-2 py-2 text-sm text-slate-500 hover:underline">Clear</a>
            @endif
        </form>

        @unless ($event->hikeDayReached())
            <p class="mb-3 text-xs text-slate-500">Attendance can be marked from the hike day.</p>
        @endunless

        <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-3">#</th>
                        <th class="px-3 py-3">Hiker</th>
                        <th class="px-3 py-3">Pickup</th>
                        <th class="px-3 py-3">Emergency contact</th>
                        <th class="px-3 py-3">Status</th>
                        @if ($paidEvent)<th class="px-3 py-3">Paid</th>@endif
                        <th class="px-3 py-3">Attendance</th>
                        <th class="px-3 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($registrations as $i => $registration)
                        <tr @class(['align-top', 'opacity-60' => $registration->isCancelled()])>
                            <td class="px-3 py-3 text-slate-400">{{ $i + 1 }}</td>
                            <td class="px-3 py-3">
                                <span class="font-medium">{{ $registration->name }}</span>
                                <span class="ml-1 font-mono text-xs text-slate-400">{{ $registration->reference }}</span>
                                <a href="https://wa.me/{{ ltrim($registration->phone, '+') }}" target="_blank" rel="noopener" class="block text-emerald-700 hover:underline">{{ $registration->phone }}</a>
                                @if ($registration->email)<span class="block text-xs text-slate-500">{{ $registration->email }}</span>@endif
                                @if ($registration->level_acknowledged_at)<span class="block text-xs text-slate-500">Level confirmed</span>@endif
                                @if ($registration->notes)<p class="mt-1 max-w-xs text-xs text-amber-800">📝 {{ $registration->notes }}</p>@endif
                                <span class="block text-xs text-slate-400" title="{{ $registration->created_at->setTimezone(config('site.timezone')) }}">booked {{ $registration->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">{{ $registration->pickup_point ? $registration->pickup_point.' '.$registration->pickupTimeShort() : '—' }}</td>
                            <td class="px-3 py-3">
                                {{ $registration->emergency_contact_name }}
                                <a href="tel:{{ $registration->emergency_contact_phone }}" class="block text-xs text-slate-500 hover:underline">{{ $registration->emergency_contact_phone }}</a>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <x-admin.badge :classes="$registration->status->badgeClasses()">{{ $registration->status->label() }}</x-admin.badge>
                                @if ($registration->isWaitlisted())<span class="block text-xs text-slate-500">#{{ $registration->waitlistPosition() }} in line</span>@endif
                                @if ($registration->isCancelled() && $registration->cancellation_reason)<span class="block max-w-[12rem] whitespace-normal text-xs text-slate-500">{{ $registration->cancellation_reason }}</span>@endif
                            </td>
                            @if ($paidEvent)
                                <td class="px-3 py-3 whitespace-nowrap">
                                    @if ($registration->paid_at)
                                        <span class="font-semibold text-emerald-700">✓ Paid</span>
                                    @elseif ($registration->holdsSeat())
                                        <span class="text-slate-400">Unpaid</span>
                                    @endif
                                    @if ($registration->payment_method)
                                        <span class="block text-xs text-slate-500">{{ $registration->payment_method->label() }}</span>
                                    @endif
                                    @if ($registration->payment_reference)
                                        <span class="block font-mono text-xs text-slate-700" title="Transaction number">#{{ $registration->payment_reference }}</span>
                                    @endif
                                    @if ($registration->refundDue())
                                        <span class="block text-xs font-bold text-red-600">Refund due</span>
                                        @if ($canManage)
                                            <form method="POST" action="{{ route('admin.registrations.refunded', $registration) }}"
                                                  onsubmit="return confirm('Did you pay this hiker back?')">@csrf
                                                <button class="text-xs font-medium text-emerald-700 hover:underline">Mark refunded</button>
                                            </form>
                                        @endif
                                    @elseif ($registration->refunded_at)
                                        <span class="block text-xs text-slate-500">Refunded {{ $registration->refunded_at->setTimezone(config('site.timezone'))->format('M d') }}</span>
                                    @endif
                                    @if ($canManage && $registration->holdsSeat())
                                        <form method="POST" action="{{ route('admin.registrations.payment', $registration) }}">@csrf
                                            <input type="hidden" name="paid" value="{{ $registration->paid_at ? 0 : 1 }}">
                                            <button class="text-xs font-medium text-emerald-700 hover:underline">{{ $registration->paid_at ? 'Mark unpaid' : 'Mark paid' }}</button>
                                        </form>
                                    @endif
                                </td>
                            @endif
                            <td class="px-3 py-3 whitespace-nowrap">
                                @if ($canAttend && $registration->isConfirmed())
                                    <div class="flex gap-1">
                                        @foreach (Attendance::cases() as $option)
                                            <form method="POST" action="{{ route('admin.registrations.attendance', $registration) }}">@csrf
                                                {{-- Clicking the active choice clears it. --}}
                                                <input type="hidden" name="attendance" value="{{ $registration->attendance === $option ? '' : $option->value }}">
                                                <button @class([
                                                    'rounded-md border px-2 py-1 text-xs font-medium',
                                                    $option->badgeClasses().' border-transparent' => $registration->attendance === $option,
                                                    'border-slate-300 text-slate-600 hover:bg-slate-50' => $registration->attendance !== $option,
                                                ]) aria-pressed="{{ $registration->attendance === $option ? 'true' : 'false' }}">{{ $option->label() }}</button>
                                            </form>
                                        @endforeach
                                    </div>
                                @elseif ($registration->attendance)
                                    <x-admin.badge :classes="$registration->attendance->badgeClasses()">{{ $registration->attendance->label() }}</x-admin.badge>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right whitespace-nowrap">
                                @if ($canManage && ! $registration->isCancelled())
                                    <div class="flex justify-end gap-3 text-sm font-medium">
                                        @if ($registration->isPending())
                                            <form method="POST" action="{{ route('admin.registrations.approve', $registration) }}">@csrf
                                                <button class="text-emerald-700 hover:underline">Approve</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('admin.registrations.edit', $registration) }}" class="text-slate-700 hover:underline">Edit</a>
                                        <button type="button" class="text-red-600 hover:underline"
                                                @click="cancel = { url: @js(route('admin.registrations.cancel', $registration)), name: @js($registration->name) }">Cancel</button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">No registrations{{ request()->hasAny(['q', 'status', 'pickup']) ? ' match these filters' : ' yet' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Cancel dialog --}}
        <div x-show="cancel" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" @keydown.escape.window="cancel = null">
            <form method="POST" :action="cancel?.url" class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl" @click.outside="cancel = null">
                @csrf
                <h2 class="text-lg font-semibold">Cancel <span x-text="cancel?.name"></span>'s booking?</h2>
                <p class="mt-1 text-sm text-slate-500">The seat goes to the first person on the waitlist, if any. This can't be undone.
                    If they already paid, refund them yourself (Whish, OMT, cash or the online provider).</p>
                <div class="mt-4">
                    <x-admin.textarea name="cancellation_reason" label="Reason (shown to the hiker)" rows="3" maxlength="500" />
                </div>
                <label class="mt-3 flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="notify" value="0">
                    <input type="checkbox" name="notify" value="1" checked class="rounded border-slate-300"> Email the hiker (if they gave an email)
                </label>
                <div class="mt-4 flex justify-end gap-2">
                    <x-admin.button variant="secondary" type="button" @click="cancel = null">Keep booking</x-admin.button>
                    <x-admin.button variant="danger">Cancel booking</x-admin.button>
                </div>
            </form>
        </div>
    </div>
</x-admin.layout>
