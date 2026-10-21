<?php

/*
| Effort score = round(multiplier × (km + gain_m / gain_per_km + loss_m / loss_per_km))
|
| Every `gain_per_km` metres of ascent count as one extra flat kilometre, and
| descent counts at a lower rate (it is tiring, but less than climbing).
| The score is mapped to a level with the upper bounds below (inclusive).
|
| Changing these values affects newly saved events; run
| `php artisan events:recalculate-difficulty` to refresh existing ones.
*/

return [

    'multiplier' => 8,

    'gain_per_km' => 100,

    'loss_per_km' => 300,

    // Highest score (inclusive) of each level; anything above the last bound is Xtreme.
    'levels' => [
        'foundation' => 99,
        'endurance' => 160,
        'advanced' => 220,
    ],

];
