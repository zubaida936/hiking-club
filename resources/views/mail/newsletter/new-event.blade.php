<x-mail::message>
# {{ $event->title }}

@if ($event->season_tag)
**{{ $event->season_tag }}**
@endif

A new hike is open for booking:

<x-mail::table>
| | |
|:--|:--|
| **Date** | {{ $event->starts_on->format('l, j F Y') }} |
| **Location** | {{ $event->location }} |
| **Level** | {{ $event->difficulty_level->label() }} |
| **Distance** | {{ rtrim(rtrim(number_format($event->distance_km, 1), '0'), '.') }} km, +{{ $event->elevation_gain_m }} m |
| **Fee** | {{ $event->formattedFee() }} |
</x-mail::table>

@if ($event->summary)
{{ $event->summary }}
@endif

<x-mail::button :url="$eventUrl">
View event
</x-mail::button>

Seats are limited, so book early.

{{ config('app.name') }}

<x-mail::subcopy>
You get this email because you subscribed to Next Destination Alerts. [Unsubscribe]({{ $unsubscribeUrl }})
</x-mail::subcopy>
</x-mail::message>
