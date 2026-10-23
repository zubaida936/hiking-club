@php($event = $registration->event)

<x-admin.layout :title="'Edit booking '.$registration->reference">
    <form method="POST" action="{{ route('admin.registrations.update', $registration) }}" class="max-w-2xl space-y-6">
        @csrf @method('PUT')

        <section class="rounded-lg bg-white p-6 shadow-sm">
            <p class="mb-4 text-sm text-slate-500">
                {{ $event->title }} · {{ $event->starts_on->format('D, M d, Y') }} ·
                <x-admin.badge :classes="$registration->status->badgeClasses()">{{ $registration->status->label() }}</x-admin.badge>
            </p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-admin.input name="name" label="Name" :value="$registration->name" required maxlength="120" />
                </div>
                <x-admin.input name="phone" label="Phone" type="tel" :value="$registration->phone" required />
                <x-admin.input name="email" label="Email" type="email" :value="$registration->email" />
                <div class="sm:col-span-2">
                    <label for="meeting_point_id" class="block text-sm font-medium text-slate-700">Pickup point</label>
                    <select id="meeting_point_id" name="meeting_point_id" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Keep: {{ $registration->pickup_point ? $registration->pickup_point.' '.$registration->pickupTimeShort() : 'none' }}</option>
                        @foreach ($event->meetingPoints as $point)
                            <option value="{{ $point->id }}" @selected((string) old('meeting_point_id') === (string) $point->id)>{{ $point->name }} {{ $point->pickupTimeShort() }}</option>
                        @endforeach
                    </select>
                    @error('meeting_point_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <x-admin.input name="emergency_contact_name" label="Emergency contact name" :value="$registration->emergency_contact_name" required maxlength="120" />
                <x-admin.input name="emergency_contact_phone" label="Emergency contact phone" type="tel" :value="$registration->emergency_contact_phone" required />
                <div class="sm:col-span-2">
                    <x-admin.textarea name="notes" label="Notes" :value="$registration->notes" rows="3" maxlength="500" />
                </div>
            </div>
        </section>

        <div class="flex gap-2">
            <x-admin.button>Save booking</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.events.registrations', $event)">Back to the list</x-admin.button>
        </div>
    </form>
</x-admin.layout>
