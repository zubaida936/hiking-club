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

    'contact' => [
        'phone' => env('SITE_PHONE', '+961 00 000 000'),
        'whatsapp' => env('SITE_WHATSAPP', '96100000000'), // digits only, used for wa.me links
        'email' => env('SITE_EMAIL', 'hello@hiking.test'),
        'instagram' => env('SITE_INSTAGRAM', 'hikingclub'),
        'facebook' => env('SITE_FACEBOOK', 'hikingclub'),
    ],

];
