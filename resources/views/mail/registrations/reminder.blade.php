<x-mail::message>
# See you on {{ $event->starts_on->format('l') }}!

Hi {{ $registration->name }}, a reminder for **{{ $event->title }}** on {{ $event->starts_on->format('l, j F Y') }} ({{ $event->location }}).

<x-mail::table>
| | |
|:--|:--|
@if ($registration->pickup_point)
| **Pickup** | **{{ $registration->pickupTimeShort() }}** at {{ $registration->pickup_point }} (Lebanon time). Please be there 5 minutes early. |
@endif
| **Level** | {{ $event->difficulty_level->label() }} · {{ $event->formattedDistance() }} · +{{ $event->elevation_gain_m }} m |
| **Booking** | {{ $registration->reference }} |
</x-mail::table>

@if ($event->what_to_bring)
**What to bring:** {{ implode(', ', $event->what_to_bring) }}.
@endif

@if ($registration->awaitsPayment())
**Payment still due:** {{ $registration->formattedAmountDue() }}{{ $registration->payment_method ? ' ('.$registration->payment_method->label().')' : '' }}. Details on your booking page.
@endif

@if ($updates->isNotEmpty())
**Latest from the organizers:**

@foreach ($updates as $update)
<x-mail::panel>
{{ $update->created_at->setTimezone(config('site.timezone'))->format('D j M') }}: {!! nl2br(e($update->message)) !!}
</x-mail::panel>
@endforeach
@endif

Check the weather before you leave and dress in layers.

<x-mail::button :url="$bookingUrl">
View my booking
</x-mail::button>

Can't make it? Cancel from My account or message us{{ $whatsappUrl ? ' on WhatsApp' : '' }} so someone on the waitlist can take your seat.

{{ config('app.name') }}
</x-mail::message>
