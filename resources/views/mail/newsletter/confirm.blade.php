<x-mail::message>
# One more step

Thanks for signing up for Next Destination Alerts from {{ config('app.name') }}!
Please confirm your email address so we can tell you about new hikes as soon as they are announced.

<x-mail::button :url="$confirmUrl">
Confirm my email
</x-mail::button>

If you didn't sign up, just ignore this email and you won't hear from us.

See you on the trail,<br>
{{ config('app.name') }}
</x-mail::message>
