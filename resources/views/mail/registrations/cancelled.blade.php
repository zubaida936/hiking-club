<x-mail::message>
@if ($event->isCancelled())
# This hike is cancelled

Hi {{ $registration->name }}, we're sorry: **{{ $event->title }}** on {{ $event->starts_on->format('l, j F Y') }} is cancelled,
so your booking (reference {{ $registration->reference }}) is cancelled too.

@if ($event->cancellation_reason)
**Why:** {{ $event->cancellation_reason }}
@endif
@else
# Your booking was cancelled

Hi {{ $registration->name }}, your booking for **{{ $event->title }}** on {{ $event->starts_on->format('l, j F Y') }} (reference {{ $registration->reference }}) has been cancelled.

@if ($registration->cancellation_reason)
**Reason:** {{ $registration->cancellation_reason }}
@endif
@endif

@if ($registration->paid_at && ! $registration->refunded_at)
**Refund:** you paid {{ $registration->formattedAmountDue() }}{{ $registration->payment_method ? ' ('.$registration->payment_method->label().')' : '' }}. We'll pay it back to you and contact you on WhatsApp to arrange it.

@endif
If you have a question, reply to this email or message us on WhatsApp with your booking reference.

<x-mail::button :url="route('events.index')">
See upcoming hikes
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
