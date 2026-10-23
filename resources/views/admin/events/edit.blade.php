<x-admin.layout :title="$event->title">
    {{-- Status bar & lifecycle actions --}}
    <div class="mb-6 flex flex-wrap items-center gap-3 rounded-lg bg-white p-4 shadow-sm" x-data="{ cancelOpen: @js($errors->has('cancellation_reason')) }">
        <x-admin.badge :classes="$event->status->badgeClasses()">{{ $event->status->label() }}</x-admin.badge>
        <span class="text-sm text-slate-500">
            @if ($event->cancelled_at)
                Cancelled {{ $event->cancelled_at->diffForHumans() }}: {{ $event->cancellation_reason }}
            @elseif ($event->published_at && $event->isPublished())
                Published {{ $event->published_at->diffForHumans() }}
            @else
                Not visible on the website
            @endif
            · Last edited {{ $event->updated_at->diffForHumans() }}{{ $event->editor ? ' by '.$event->editor->name : '' }}
        </span>

        <div class="ml-auto flex flex-wrap gap-2">
            @if ($event->isVisibleOnSite())
                <x-admin.button variant="secondary" :href="route('events.show', $event)" target="_blank">View on site</x-admin.button>
            @endif

            @if ($event->status === \App\Domain\Events\Enums\EventStatus::Draft)
                <form method="POST" action="{{ route('admin.events.publish', $event) }}">@csrf
                    <x-admin.button>Publish</x-admin.button>
                </form>
            @elseif ($event->isPublished())
                <form method="POST" action="{{ route('admin.events.unpublish', $event) }}">@csrf
                    <x-admin.button variant="secondary">Unpublish</x-admin.button>
                </form>
            @endif

            <form method="POST" action="{{ route('admin.events.duplicate', $event) }}">@csrf
                <x-admin.button variant="secondary">Duplicate</x-admin.button>
            </form>

            @if ($event->status !== \App\Domain\Events\Enums\EventStatus::Cancelled)
                <x-admin.button variant="secondary" type="button" @click="cancelOpen = true">Cancel event</x-admin.button>
            @endif

            @unless ($event->isPublished())
                <form method="POST" action="{{ route('admin.events.destroy', $event) }}"
                      onsubmit="return confirm('Delete this event? It can be restored by a developer.')">@csrf @method('DELETE')
                    <x-admin.button variant="danger">Delete</x-admin.button>
                </form>
            @endunless
        </div>

        {{-- Cancel dialog --}}
        <div x-show="cancelOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" @keydown.escape.window="cancelOpen = false">
            <form method="POST" action="{{ route('admin.events.cancel', $event) }}" class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl" @click.outside="cancelOpen = false">
                @csrf
                <h2 class="text-lg font-semibold">Cancel this event?</h2>
                <p class="mt-1 text-sm text-slate-500">The reason is shown to hikers. This can't be undone; duplicate the event to reschedule.</p>
                <div class="mt-4">
                    <x-admin.textarea name="cancellation_reason" label="Reason" rows="3" required maxlength="500" />
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <x-admin.button variant="secondary" type="button" @click="cancelOpen = false">Keep event</x-admin.button>
                    <x-admin.button variant="danger">Cancel event</x-admin.button>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.events.update', $event) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.events._form')
        <div class="mt-6 flex gap-2">
            <x-admin.button>Save changes</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.events.index')">Back to events</x-admin.button>
        </div>
    </form>

    @can(\App\Domain\Identity\Enums\Permission::ViewRegistrations->value)
        @php($active = $event->registrations->filter(fn ($r) => $r->status !== \App\Domain\Registrations\Enums\RegistrationStatus::Cancelled))
        <section id="registrations" class="mt-10 rounded-lg bg-white p-6 shadow-sm">
            <h2 class="font-semibold">
                Registrations
                <span class="ml-2 text-sm font-normal text-slate-500">{{ $active->count() }} / {{ $event->capacity }} seats taken</span>
            </h2>

            @if ($event->registrations->isEmpty())
                <p class="mt-3 text-sm text-slate-500">No registrations yet.</p>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-200 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="py-2 pr-4">#</th>
                                <th class="py-2 pr-4">Ref</th>
                                <th class="py-2 pr-4">Name</th>
                                <th class="py-2 pr-4">Phone</th>
                                <th class="py-2 pr-4">Pickup</th>
                                <th class="py-2 pr-4">Emergency contact</th>
                                <th class="py-2 pr-4">Notes</th>
                                <th class="py-2 pr-4">Status</th>
                                <th class="py-2">Booked</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($event->registrations as $i => $registration)
                                <tr class="align-top">
                                    <td class="py-2 pr-4 text-slate-400">{{ $i + 1 }}</td>
                                    <td class="py-2 pr-4 font-mono">{{ $registration->reference }}</td>
                                    <td class="py-2 pr-4">
                                        {{ $registration->name }}
                                        @if ($registration->email)<span class="block text-xs text-slate-500">{{ $registration->email }}</span>@endif
                                        @if ($registration->level_acknowledged_at)<span class="block text-xs text-slate-500">Level confirmed</span>@endif
                                    </td>
                                    <td class="py-2 pr-4 whitespace-nowrap">
                                        <a href="https://wa.me/{{ ltrim($registration->phone, '+') }}" target="_blank" rel="noopener" class="text-emerald-700 hover:underline">{{ $registration->phone }}</a>
                                    </td>
                                    <td class="py-2 pr-4">{{ $registration->pickup_point ? $registration->pickup_point.' '.$registration->pickupTimeShort() : '—' }}</td>
                                    <td class="py-2 pr-4">{{ $registration->emergency_contact_name }}<span class="block text-xs text-slate-500">{{ $registration->emergency_contact_phone }}</span></td>
                                    <td class="max-w-xs py-2 pr-4 text-slate-600">{{ $registration->notes }}</td>
                                    <td class="py-2 pr-4"><x-admin.badge :classes="$registration->status->badgeClasses()">{{ $registration->status->label() }}</x-admin.badge></td>
                                    <td class="py-2 whitespace-nowrap text-slate-500" title="{{ $registration->created_at }}">{{ $registration->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endcan
</x-admin.layout>
