<?php

/*
| Shop (ClickUp list "6. Shop"). Payment methods are shared with event bookings
| (config/payments.php): Whish / OMT transfer, cash, and online when a gateway is set.
| PLACEHOLDER amounts and texts until the club decides.
*/

return [

    // Flat delivery fee (cents) for "Delivery"; pickup is free.
    'delivery_fee_cents' => (int) env('SHOP_DELIVERY_FEE_CENTS', 400),

    'pickup_note' => 'Collect your order from the organizers at the pickup point of any upcoming hike. We confirm on WhatsApp when it is ready.',

    'delivery_note' => 'Delivery anywhere in Lebanon within 3–5 working days. Pay the driver in cash, or pay before with Whish / OMT / online.',

    // Where "new order" emails go (the shop team).
    'notify_email' => env('SHOP_NOTIFY_EMAIL', env('SITE_EMAIL', 'hello@hiking.test')),

    // Stock at or below this shows "N left" on the product card.
    'low_stock' => 5,

    // Most items of one variant in a single order.
    'max_quantity' => 10,

];
