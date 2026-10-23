<x-mail::message>
# Reset your password

Hi {{ $user->name }}, use this code to choose a new password for your {{ config('app.name') }} account:

<x-mail::panel>
<span style="font-size: 28px; font-weight: bold; letter-spacing: 6px;">{{ $code }}</span>
</x-mail::panel>

The code works for {{ $minutes }} minutes. If you didn't ask to reset your password, you can ignore this email:
your password stays the same.

{{ config('app.name') }}
</x-mail::message>
