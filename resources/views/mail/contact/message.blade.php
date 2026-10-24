<x-mail::message>
# New message from the website

**{{ $data->name }}** · {{ $data->email }}{{ $data->phone ? ' · '.$data->phone : '' }}

<x-mail::panel>
{{ $data->message }}
</x-mail::panel>

Reply to this email to answer {{ $data->name }} directly.
@if ($whatsapp)

<x-mail::button :url="$whatsapp">Answer on WhatsApp</x-mail::button>
@endif
</x-mail::message>
