<?php

/*
| Points engine (ClickUp "Points engine"). Points are earned when an organizer
| marks a confirmed booking as attended. After changing these values run
| `php artisan points:sync` to bring every hiker's ledger up to date.
*/

return [

    // Per completed hike, by difficulty level (1 Foundation/Easy … 4 Xtreme).
    'hike' => [
        1 => 8,
        2 => 14,
        3 => 22,
        4 => 35,
    ],

    // Bonus for booking at least `days` days before the hike day (club time).
    'early_booking' => [
        'points' => 4,
        'days' => 5,
    ],

    // For the member who brought a new hiker, once that hiker completes their first hike.
    'referral' => 10,

    // Only paid hikes count (free hikes always count). Set false while payment isn't tracked.
    'paid_only' => true,

    // Rank tiers: the points a hiker needs to reach each one (ClickUp "Rank tiers").
    // Hikers with equal points are ranked by total distance hiked.
    'tiers' => [
        'pathfinder' => 0,
        'trekker' => 80,
        'mountaineer' => 200,
        'highlander' => 400,
        'elite' => 700,
    ],

];
