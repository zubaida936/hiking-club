<x-mail::message>
@if ($registration->isWaitlisted())
# You're on the waitlist

Thanks, {{ $registration->name }}! **{{ $event->title }}** is fully booked, so you're **number {{ $registration->waitlistPosition() }}**
on the waitlist. If a seat opens up, you move up automatically and we email you right away.
@elseif ($promoted)
# Good news: a seat opened up!

{{ $registration->name }}, you've moved up from the waitlist for **{{ $event->title }}**.
@if ($registration->isPending())
It's an X Event, so an organizer will still review your registration before confirming the seat.
@else
Your seat is confirmed.
@endif
@elseif ($registration->isPending())
# Registration received

Thanks, {{ $registration->name }}! **{{ $event->title }}** is an X Event, so an organizer will review your
registration and confirm your seat. Your seat is held in the meantime.
@else
# You're booked!

Thanks, {{ $registration->name }}! Your seat on **{{ $event->title }}** is confirmed.
@endif

<x-mail::table>
| | |
|:--|:--|
| **Booking reference** | {{ $registration->reference }} |
| **Date** | {{ $event->starts_on->format('l, j F Y') }} |
@if ($registration->pickup_point)
| **Pickup** | {{ $registration->pickup_point }} at {{ $registration->pickupTimeShort() }} (Lebanon time) |
@endif
| **Level** | {{ $event->difficulty_level->label() }} |
| **Fee** | {{ $event->formattedFee() }} |
</x-mail::table>

@if ($event->fee_cents > 0 && $registration->paid_at === null)
@switch($registration->payment_method?->value)
@case('whish')
@case('omt')
**Payment:** send {{ $event->formattedFee() }} by {{ $registration->payment_method->label() }} to {{ $registration->payment_method->accountName() }}, {{ $registration->payment_method->accountNumber() }}, with your booking reference {{ $registration->reference }} in the note{{ $registration->isConfirmed() ? '' : ' once your seat is confirmed' }}.
@break
@case('cash')
**Payment:** {{ $event->formattedFee() }} in cash to the organizers at the pickup point.
@break
@case('online')
**Payment:** pay {{ $event->formattedFee() }} online from your booking page{{ $registration->isConfirmed() ? '' : ' once your seat is confirmed' }}.
@break
@default
{{ config('site.booking.payment_note') }}
@endswitch
@endif

<x-mail::button :url="$bookingUrl">
View my booking
</x-mail::button>

Need to change or cancel? Reply to this email or message us on WhatsApp with your booking reference.

See you on the trail,<br>
{{ config('app.name') }}
</x-mail::message>
