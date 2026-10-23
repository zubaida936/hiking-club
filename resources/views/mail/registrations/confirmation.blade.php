<x-mail::message>
@if ($registration->isPending())
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
