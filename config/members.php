<?php

use App\Domain\Members\Codes\LogCodeChannel;
use App\Domain\Members\Codes\MailCodeChannel;

/*
| Member accounts (hikers). No open signup: hikers who booked a hike activate an
| account with their phone number and a booking reference, then log in with phone + password.
*/

return [

    /*
    | How password-reset codes reach the hiker:
    |   mail - to the email on the hiker's account (works today)
    |   log  - written to the application log (local development only)
    | SMS / WhatsApp needs a provider (ClickUp "Login & account activation"); add a
    | channel class implementing App\Domain\Members\Codes\CodeChannel and map it here.
    */
    'code_channel' => env('MEMBER_CODE_CHANNEL', 'mail'),

    'code_channels' => [
        'mail' => MailCodeChannel::class,
        'log' => LogCodeChannel::class,
    ],

    // Password-reset codes: valid for this many minutes, with this many wrong tries.
    'code_ttl_minutes' => 10,
    'code_max_attempts' => 5,

    'password_min_length' => 8,

];
