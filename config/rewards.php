<?php

/*
| Rewards per rank (ClickUp "Rewards per tier & redemption"). One-time unless
| `uses` says otherwise, personal (non-transferable), one reward per booking (no stacking).
|
| kind:
|   discount     - % off the fee of one booking, chosen by the member on the booking form
|                  (`levels` limits it to hikes of those difficulty levels; omit for any hike)
|   bonus_points - points added automatically once, when the member reaches the rank
|   perk         - handed over by an organizer, who marks it used in CMS -> Points -> hiker
|   xtreme       - X event bookings are confirmed straight away instead of awaiting approval
|   info         - shown on the dashboard only (no tracking yet)
|
| `min_completed_hikes`: extra condition (completed hikes that count for points).
| Changing a key after rewards were used detaches those uses: add new keys instead.
*/

return [

    'trekker_half_off' => [
        'tier' => 'trekker',
        'label' => '50% off one hike',
        'kind' => 'discount',
        'percent' => 50,
        'uses' => 1,
        'min_completed_hikes' => 5,
    ],

    'mountaineer_free_moderate' => [
        'tier' => 'mountaineer',
        'label' => 'One free Moderate (Level 2) hike',
        'kind' => 'discount',
        'percent' => 100,
        'uses' => 1,
        'levels' => [2],
    ],

    'mountaineer_bonus_points' => [
        'tier' => 'mountaineer',
        'label' => '+5 bonus points',
        'kind' => 'bonus_points',
        'points' => 5,
    ],

    'highlander_challenging_15' => [
        'tier' => 'highlander',
        'label' => '15% off two Challenging (Level 3) hikes',
        'kind' => 'discount',
        'percent' => 15,
        'uses' => 2,
        'levels' => [3],
    ],

    'highlander_gift_card' => [
        'tier' => 'highlander',
        'label' => 'Gift card',
        'kind' => 'perk',
        'uses' => 1,
    ],

    'highlander_winter_bonus' => [
        'tier' => 'highlander',
        'label' => 'Winter bonus',
        'kind' => 'perk',
        'uses' => 1,
    ],

    'elite_free_hike' => [
        'tier' => 'elite',
        'label' => 'One free hike',
        'kind' => 'discount',
        'percent' => 100,
        'uses' => 1,
    ],

    'elite_xtreme' => [
        'tier' => 'elite',
        'label' => 'Eligible for Xtreme hikes (no approval needed)',
        'kind' => 'xtreme',
    ],

    'elite_invite_only' => [
        'tier' => 'elite',
        'label' => 'Invite-only events',
        'kind' => 'info',
    ],

];
