<?php

/*
| Equipment guide (/equipment): "What You Need for Each Adventure".
| PLACEHOLDER lists until the club's guides review them.
|
| Each item: [name, why / tip, needed from difficulty level (1-4, null = always), shop category slug or null].
| A shop link is shown only when that category has products in the shop.
*/

return [

    'adventures' => [
        'hiking' => [
            'label' => 'Hiking',
            'icon' => '🥾',
            'intro' => 'Day hikes on trails and mountain paths. Pick your level to see what to pack: harder hikes add to the list.',
            'items' => [
                ['Hiking shoes or boots', 'Broken in, with a good grip. Ankle support from Level 2.', null, 'footwear'],
                ['Backpack 20-30 L', 'Hip belt and padded straps; a rain cover helps in winter.', null, 'backpack'],
                ['Water', '1.5 L at Level 1, 2-3 L on longer days. Half a litre per hour of walking.', null, null],
                ['Snacks and lunch', 'Nuts, dried fruit, sandwiches: enough for the whole day.', null, null],
                ['Sun protection', 'Cap, sunglasses and sunscreen, even when it is cloudy.', null, 'accessories'],
                ['Warm layer', 'A fleece or light jacket for breaks and summits.', null, 'clothing'],
                ['ID and some cash', 'For checkpoints and the village shop on the way back.', null, null],
                ['Rain jacket', 'Waterproof and breathable; weather changes fast in the mountains.', 2, 'jacket'],
                ['Hiking poles', 'Save your knees on long descents.', 2, 'accessories'],
                ['Electrolytes', 'Salts for long or hot days.', 2, null],
                ['Headlamp', 'Long days can end after sunset. Spare batteries.', 3, 'accessories'],
                ['Blister kit', 'Plasters, tape and a small first-aid kit.', 3, null],
                ['Extra layers', 'Gloves, beanie and a spare pair of socks.', 3, 'clothing'],
                ['Water filter or tablets', 'To refill from springs on very long routes.', 4, null],
                ['Emergency blanket', 'Light, and keeps you warm if you have to wait.', 4, null],
            ],
        ],

        'snowshoeing' => [
            'label' => 'Snowshoeing',
            'icon' => '❄️',
            'intro' => 'Winter hikes in the snow. Snowshoes and poles can be rented through the club: ask when you book.',
            'items' => [
                ['Waterproof boots', 'Warm and waterproof, with gaiters to keep snow out.', null, 'footwear'],
                ['Snowshoes and poles', 'Rental available, ask the organizers.', null, null],
                ['Thermal base layer', 'No cotton: it stays wet and cold.', null, 'clothing'],
                ['Insulated jacket', 'Plus a waterproof shell on top.', null, 'jacket'],
                ['Gloves and beanie', 'Bring a spare pair of gloves.', null, 'accessories'],
                ['Sunglasses and sunscreen', 'Sun on the snow burns twice as fast.', null, 'accessories'],
                ['Hot drink in a thermos', 'Tea at the summit is part of the tradition.', null, null],
                ['Snacks', 'You burn more energy in the cold.', null, null],
            ],
        ],

        'rafting' => [
            'label' => 'Rafting',
            'icon' => '🚣',
            'intro' => 'Rafting on the Assi river with a licensed operator. Helmets, life jackets and paddles are provided.',
            'items' => [
                ['Swimsuit or quick-dry clothes', 'You will get wet.', null, 'clothing'],
                ['Water shoes or old sneakers', 'Tied tight; no flip-flops.', null, 'footwear'],
                ['Change of clothes and a towel', 'Leave them in the bus.', null, null],
                ['Sunscreen', 'Waterproof.', null, 'accessories'],
                ['Strap for your glasses', 'Or leave them at home.', null, 'accessories'],
                ['Dry bag', 'For your phone and keys, if you bring them.', null, 'backpack'],
            ],
        ],

        'camping' => [
            'label' => 'Camping',
            'icon' => '⛺',
            'intro' => 'Overnight trips. Tents can be shared: tell us when you book if you need a spot in one.',
            'items' => [
                ['Tent', 'Or a spot in a shared tent.', null, null],
                ['Sleeping bag', 'Rated for the night temperature (mountain nights are cold).', null, null],
                ['Sleeping mat', 'Insulates you from the ground.', null, null],
                ['Headlamp', 'With spare batteries.', null, 'accessories'],
                ['Warm layers', 'Fleece, beanie, warm socks for the evening.', null, 'clothing'],
                ['Backpack 40-60 L', 'Room for your night gear.', null, 'backpack'],
                ['Mug, plate and cutlery', 'Reusable, we cook together.', null, null],
                ['Toiletries and a small towel', 'Biodegradable soap; leave no trace.', null, null],
                ['Water and snacks', 'For the hike in.', null, null],
            ],
        ],
    ],

];
