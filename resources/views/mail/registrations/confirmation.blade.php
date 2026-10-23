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

{{ config('site.booking.payment_note') }}

<x-mail::button :url="$bookingUrl">
View my booking
</x-mail::button>

Need to change or cancel? Reply to this email or message us on WhatsApp with your booking reference.

See you on the trail,<br>
{{ config('app.name') }}
</x-mail::message>
