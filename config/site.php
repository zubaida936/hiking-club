<?php

/*
| Public-site copy and contact details.
|
| Everything here is PLACEHOLDER content until branding is decided (ClickUp
| "Decide tech stack, hosting & branding"). The hero slides and texts move to
| the CMS with the "Manage content" task; contact details stay here.
*/

return [

    'tagline' => 'Trails, summits and good company, every weekend.',

    /*
    | SEO defaults, used when a page doesn't set its own description / share image.
    | The share image should be 1200×630 JPEG or PNG (path under public/).
    */
    // Keep it under 160 characters: search results cut longer text.
    'description' => 'Guided hikes across Lebanon for every level: weekend day hikes, snowshoe trips and X Events. '
        .'Transport, guides and a friendly crew included.',
    'share_image' => 'images/og-default.jpg',

    /*
    | Google Analytics 4 measurement ID (G-XXXXXXX). Empty = no analytics script at all.
    */
    'analytics' => [
        'ga4_id' => env('GOOGLE_ANALYTICS_ID'),
    ],

    'about' => 'We are a community of hikers exploring the mountains, valleys and cedar forests of Lebanon. '
        .'Every week we organise guided hikes for every level, from easy nature walks to demanding summit days, '
        .'with transport, experienced guides and a crew that looks after each other on the trail.',

    /*
    | Seasonal hero carousel. Each slide: season label, heading, text, CTA label + route name.
    | 'image' is optional (path under public/); slides without one use a gradient.
    */
    'hero' => [
        [
            'season' => '🍂 Autumn',
            'heading' => 'Chase the autumn colours',
            'text' => 'Golden forests, crisp air and the best light of the year.',
            'cta' => 'See autumn hikes',
            'route' => 'events.index',
            'image' => null,
        ],
        [
            'season' => '❄️ Winter',
            'heading' => 'Snowshoe the high peaks',
            'text' => 'Fresh snow, silent ridges and hot tea at the summit.',
            'cta' => 'See snow hikes',
            'route' => 'events.index',
            'image' => null,
        ],
        [
            'season' => '🌸 Spring',
            'heading' => 'Walk through wildflowers',
            'text' => 'Green valleys, waterfalls and trails in full bloom.',
            'cta' => 'See spring hikes',
            'route' => 'events.index',
            'image' => null,
        ],
    ],

    /*
    | Main menu, used by the header and the footer site map. Items link by route
    | name; 'active' is the route pattern that highlights the item in the header.
    */
    'menu' => [
        ['label' => 'Home', 'route' => 'home', 'active' => 'home'],
        ['label' => 'About', 'route' => 'about', 'active' => 'about'],
        ['label' => 'Events', 'route' => 'events.index', 'active' => 'events.*'],
        ['label' => 'X Events', 'route' => 'x-events', 'active' => 'x-events*'],
        ['label' => 'Shop', 'route' => 'shop.index', 'active' => 'shop.*'],
        ['label' => 'Rank System', 'route' => 'rank-system', 'active' => 'rank-system'],
        ['label' => "Hiker's Guide", 'active' => 'guide.*', 'children' => [
            ['label' => 'Difficulty Calculator', 'route' => 'guide.difficulty'],
            ['label' => 'Equipment Guide', 'route' => 'guide.equipment'],
            ['label' => 'Guides', 'route' => 'guide.articles'],
        ]],
        ['label' => 'Contact', 'route' => 'contact', 'active' => 'contact'],
    ],

    'contact' => [
        'address' => env('SITE_ADDRESS', 'Beirut, Lebanon'),
        'phone' => env('SITE_PHONE', '+961 00 000 000'),
        'whatsapp' => env('SITE_WHATSAPP', '96100000000'), // international number, digits only; empty hides WhatsApp links
        // Pre-filled text of the floating WhatsApp button (event pages pass their own).
        'whatsapp_message' => 'Hi! I have a question about your hikes.',
        'email' => env('SITE_EMAIL', 'hello@hiking.test'),
    ],

    /*
    | Event bookings. PLACEHOLDER texts until the "Event payments" task decides how hikers pay.
    */
    'booking' => [
        // Shown on the confirmation page and in the confirmation email.
        'payment_note' => 'The organizers will contact you on WhatsApp with the payment details.',
        // Short terms hikers accept on the booking form (the full text comes with the Legal task).
        'terms' => 'I take part at my own risk, I will follow the guides\' instructions, and I agree that the club may contact me about this hike by phone, WhatsApp or email.',
    ],

    /*
    | Social account handles (without @). Leave one empty to hide its link.
    */
    'social' => [
        'facebook' => env('SITE_FACEBOOK', 'hikingclub'),
        'instagram' => env('SITE_INSTAGRAM', 'hikingclub'),
        'tiktok' => env('SITE_TIKTOK', 'hikingclub'),
        'youtube' => env('SITE_YOUTUBE', 'hikingclub'),
    ],

];
