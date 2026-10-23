<?php

use App\Domain\Payments\Gateways\TestGateway;

/*
| Event payments (ClickUp "Event payments"). Hikers choose how they pay when they
| book a paid hike; organizers confirm manual payments with "Mark paid" in the CMS,
| online payments are confirmed automatically.
|
| Wallet numbers and names are PLACEHOLDERS: set them in .env.
*/

return [

    'currency' => 'USD',

    // Manual methods hikers can choose. Remove a key to hide that option.
    'methods' => [
        'whish' => [
            'label' => 'Whish Money transfer',
            'account_name' => env('PAYMENT_WHISH_NAME', 'Hiking Club'),
            'account_number' => env('PAYMENT_WHISH_NUMBER', '+961 70 000 000'),
        ],
        'omt' => [
            'label' => 'OMT Pay transfer',
            'account_name' => env('PAYMENT_OMT_NAME', 'Hiking Club'),
            'account_number' => env('PAYMENT_OMT_NUMBER', '+961 70 000 000'),
        ],
        'cash' => [
            'label' => 'Cash on the day',
        ],
    ],

    /*
    | Online payment ("Pay now" on the booking page). null hides it.
    |   test - built-in simulator (never in production): a page with "Pay" / "Fail" buttons
    | Real providers (Whish Money, OMT Pay, a bank gateway) are added as a class implementing
    | App\Domain\Payments\Gateways\PaymentGateway once the club has merchant API access.
    */
    'online_gateway' => env('PAYMENT_ONLINE_GATEWAY'),

    'gateways' => [
        'test' => TestGateway::class,
    ],

];
