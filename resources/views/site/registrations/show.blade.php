@php
    $heading = match (true) {
        $registration->isWaitlisted() => "You're on the waitlist",
        $registration->isPending() => 'Registration received',
        default => "You're booked!",
    };
@endphp

<x-site.layout :title="rtrim($heading, '!')" :noindex="true">
    <section class="bg-brand-800 px-4 py-14 text-center text-white sm:px-6">
        <p class="font-display text-sm font-semibold uppercase tracking-[0.2em] text-accent-400">Booking reference</p>
        <p class="mt-2 font-display text-4xl font-bold tracking-[0.15em]">{{ $registration->reference }}</p>
        <h1 class="mt-6 font-display text-3xl font-bold uppercase tracking-wide sm:text-4xl">{{ $heading }}</h1>
        <p class="mx-auto mt-3 max-w-xl text-white/85">
            @if ($registration->status === \App\Domain\Registrations\Enums\RegistrationStatus::Cancelled)
                This booking has been cancelled.
            @elseif ($registration->isWaitlisted())
                {{ $event->title }} is fully booked. You're number {{ $registration->waitlistPosition() }} on the waitlist: if a seat opens up you move up automatically{{ $registration->email ? ' and we email you' : '' }}.
            @elseif ($registration->isPending())
                {{ $event->title }} is an X Event. An organizer will review your registration and confirm your seat; it's held for you in the meantime.
            @else
                Your seat on {{ $event->title }} is confirmed. See you on the trail, {{ Str::before($registration->name, ' ') }}!
            @endif
        </p>
    </section>

    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6">
        @if (session('status'))
            <p class="mb-6 rounded-xl bg-brand-50 px-5 py-4 font-medium text-brand-900" role="status">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p class="mb-6 rounded-xl bg-red-50 px-5 py-4 font-medium text-red-800" role="alert">{{ session('error') }}</p>
        @endif

        <dl class="divide-y divide-slate-100 rounded-2xl border border-slate-200">
            @foreach (array_filter([
                'Hike' => $event->title,
                'Date' => $event->starts_on->format('l, j F Y'),
                'Pickup' => $registration->pickup_point ? $registration->pickup_point.' at '.$registration->pickupTimeShort().' (Lebanon time)' : null,
                'Level' => $event->difficulty_level->label(),
                'Fee' => $event->formattedFee(),
                'Name' => $registration->name,
                'Phone' => $registration->phone,
                'Email' => $registration->email,
                'Emergency contact' => $registration->emergency_contact_name.', '.$registration->emergency_contact_phone,
                'Status' => $registration->status->label(),
            ]) as $label => $value)
                <div class="flex justify-between gap-4 px-5 py-3">
                    <dt class="text-slate-500">{{ $label }}</dt>
                    <dd class="text-right font-semibold text-slate-800">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        <x-site.booking-payment :registration="$registration" />
        @unless ($registration->isCancelled())
            <p class="mt-2 text-slate-600">
                You earn <strong class="text-accent-600">{{ app(\App\Domain\Loyalty\PointsRules::class)->hikePoints($event) }} points</strong> when you complete this hike{{ config('points.paid_only') && $event->fee_cents > 0 ? ' (paid bookings only)' : '' }}.
            </p>
        @endunless
        <p class="mt-2 text-slate-600">
            @if ($registration->email)
                We've sent a confirmation to <strong>{{ $registration->email }}</strong>.
            @endif
            Keep this page's link to come back to your booking. To change or cancel, message us with your reference.
        </p>

        <div class="mt-8 flex flex-wrap gap-3">
            @if ($whatsappUrl)
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener"
                   class="flex items-center gap-2 rounded-full bg-[#25D366] px-6 py-3 font-display font-bold uppercase tracking-wide text-white hover:bg-[#1ebe5b]">
                    <x-site.social-icon name="whatsapp" class="size-5" /> Send us your booking
                </a>
            @endif
            <a href="{{ route('events.show', $event) }}"
               class="rounded-full border border-brand-600 px-6 py-3 font-display font-bold uppercase tracking-wide text-brand-700 hover:bg-brand-50">
                Hike details
            </a>
        </div>
    </div>
</x-site.layout>
