<?php

/*
| X Events (ClickUp list "5. X Events"): the choices on the application form.
| Keys are stored with each application; change labels freely, add new keys rather
| than renaming existing ones.
*/

return [

    // Applicants under this age need a parent or guardian's details.
    'guardian_under_age' => 18,

    // The "I agree" box at the end of the application form (website and app), followed by a privacy policy link.
    'consent' => 'My answers are true. I understand X Events are demanding and I take part at my own risk. The club may keep these answers to assess X Events access and contact me on WhatsApp.',

    // The X Events page texts (website /x-events and the app's X Events screen).
    'page' => [
        'heading' => 'Longer routes. Sustained effort. Prepared hikers.',
        'intro' => 'Our most demanding days on the trail: big distances, big climbs and long hours, for hikers ready for them.',
        'requirements' => [
            ['title' => 'Endurance', 'text' => 'You regularly complete long hikes (20 km+, 1,000 m+ of climbing) and keep a steady pace for 8 hours or more.'],
            ['title' => 'Preparation', 'text' => 'You train between hikes, know your limits and come with the right gear, water and food for a long day.'],
            ['title' => 'Team discipline', 'text' => 'You follow the guides, stay with the group, respect the timings and look after the hikers around you.'],
        ],
        'steps' => [
            ['title' => 'Apply once', 'text' => 'Tell us about your experience, your hardest hikes and your gear. It takes about 5 minutes.'],
            ['title' => 'Manual review', 'text' => 'Our guides read every application. We may call you on WhatsApp with a few questions.'],
            ['title' => 'Personal access', 'text' => 'Once approved, you get a personal link: log in and book any X event, now and in the future.'],
        ],
        'no_events' => "The next X event is being planned. Apply now so you're ready when it's announced.",
    ],

    'frequency' => [
        'weekly' => 'Every week',
        'twice_month' => 'Two or three times a month',
        'monthly' => 'About once a month',
        'few_year' => 'A few times a year',
    ],

    'pace' => [
        'relaxed' => 'Relaxed (under 3 km/h on mixed terrain)',
        'steady' => 'Steady (3–4 km/h)',
        'fast' => 'Fast (over 4 km/h)',
    ],

    'experience' => [
        'night' => 'Night hikes',
        'winter' => 'Winter / snow hikes',
        'multi_day' => 'Multi-day treks',
        'technical' => 'Technical terrain (scrambling, exposed paths)',
        'solo' => 'Solo hikes',
    ],

    'gear' => [
        'boots' => 'Broken-in hiking boots',
        'backpack' => 'Day pack (25 L or more)',
        'water' => 'At least 3 L of water capacity',
        'headlamp' => 'Headlamp with spare batteries',
        'layers' => 'Warm and waterproof layers',
        'first_aid' => 'Personal first-aid kit',
        'poles' => 'Trekking poles',
        'spikes' => 'Microspikes or crampons (winter)',
    ],

];
