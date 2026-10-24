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

    // The club's local time: pickup times and booking deadlines are entered and shown in it (the app stores UTC).
    'timezone' => 'Asia/Beirut',

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
    | About page. PLACEHOLDER story, offers and testimonials until the club writes its own
    | (team members come from CMS → Team). 'google_reviews_url': the club's Google Maps reviews
    | link; empty hides the "Read our Google reviews" button.
    */
    'about_page' => [
        'founded' => (int) env('SITE_FOUNDED_YEAR', 2018),
        'story' => [
            'It started with a handful of friends, a borrowed minibus and a sunrise hike up Qornet es Sawda.',
            'Word spread, and every weekend more people joined: students, families, seasoned trekkers and first-timers who had never left the city. Today we organise hikes every week across Lebanon, from the cedar forests of the north to the valleys of the south.',
            'Our promise has not changed: safe, well-organised hikes led by people who know the mountains, and a crew that looks after each other on the trail.',
        ],
        'offers' => [
            ['⛰️', 'Challenging hikes', 'From easy nature walks to demanding summit days and our X Events for prepared hikers.'],
            ['🧭', 'Expert-led', 'Experienced guides with first-aid training lead every hike and know each trail by heart.'],
            ['🌲', 'Hidden trails', 'Routes off the beaten path: secret valleys, old shepherd paths and springs most people never see.'],
            ['🤝', 'Community', 'A friendly crew of hikers of every age and level. Come alone, leave with friends.'],
            ['🍂', 'Seasonal adventures', 'Snowshoeing in winter, wildflowers in spring, night hikes in summer and golden forests in autumn.'],
        ],
        'testimonials' => [
            ['Rima H.', 'My first hike ever and I felt safe the whole way. The guides waited for everyone and the views were unreal.'],
            ['Karim S.', 'Perfectly organised from the bus to the summit. I have joined twelve hikes and every one was different.'],
            ['Maya D.', 'I came alone and now I have a whole group of hiking friends. The best part of my weekends.'],
        ],
        'google_reviews_url' => env('SITE_GOOGLE_REVIEWS_URL'),
    ],

    /*
    | Seasonal hero carousel placeholders, shown only while CMS → Hero slides has no active
    | slide. Each slide: season label, heading, text, CTA label + route name.
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
        ['label' => 'Gallery', 'route' => 'gallery.index', 'active' => 'gallery.*'],
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
        'email' => env('SITE_EMAIL', 'hello@hiking.test'), // also receives the contact-form messages
        // Google Maps link of the club's place (Share → Copy link); empty = a map search for the address.
        'maps_url' => env('SITE_MAPS_URL'),
    ],

    /*
    | Event bookings. PLACEHOLDER texts until the "Event payments" task decides how hikers pay.
    */
    'booking' => [
        // Paid bookings can be cancelled online (and refunded) until this many days before the hike day,
        // Lebanon time: 2 = for a Saturday hike, until Wednesday 23:59. Unpaid ones until bookings close.
        'paid_cancellation_days' => 2,
        // Reminder email to booked hikers: this many days before the hike day, from this hour (Lebanon time).
        // Sent by the scheduler (`php artisan events:send-reminders`, every hour).
        'reminder_days_before' => 1,
        'reminder_hour' => 17,

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
