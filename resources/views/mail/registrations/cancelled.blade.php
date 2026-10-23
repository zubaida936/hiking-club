<x-mail::message>
# Your booking was cancelled

Hi {{ $registration->name }}, your booking for **{{ $event->title }}** on {{ $event->starts_on->format('l, j F Y') }} (reference {{ $registration->reference }}) has been cancelled.

@if ($registration->cancellation_reason)
**Reason:** {{ $registration->cancellation_reason }}
@endif

If you think this is a mistake, reply to this email or message us on WhatsApp with your booking reference.

<x-mail::button :url="route('events.index')">
See upcoming hikes
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
