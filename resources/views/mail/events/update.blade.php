<x-mail::message>
# Update about your hike

Hi {{ $registration->name }}, here's news from the organizers about **{{ $event->title }}** on {{ $event->starts_on->format('l, j F Y') }}:

<x-mail::panel>
{!! nl2br(e($eventUpdate->message)) !!}
</x-mail::panel>

<x-mail::button :url="$bookingUrl">
View my booking
</x-mail::button>

Questions? Reply to this email or message us on WhatsApp with your booking reference ({{ $registration->reference }}).

{{ config('app.name') }}
</x-mail::message>
