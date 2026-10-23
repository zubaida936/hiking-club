<?php

/*
| X Events (ClickUp list "5. X Events"): the choices on the application form.
| Keys are stored with each application; change labels freely, add new keys rather
| than renaming existing ones.
*/

return [

    // Applicants under this age need a parent or guardian's details.
    'guardian_under_age' => 18,

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
