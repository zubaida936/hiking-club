<?php

use App\Http\Controllers\Site\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Placeholder pages so every header link works today. Each feature replaces its own line
// with a real controller route (keep the route name: the header links by name).
$placeholders = [
    'about' => ['about', 'About'],
    'events' => ['events.index', 'Events'],
    'x-events' => ['x-events', 'X Events'],
    'shop' => ['shop.index', 'Shop'],
    'rank-system' => ['rank-system', 'Rank System'],
    'contact' => ['contact', 'Contact'],
    'difficulty-calculator' => ['guide.difficulty', 'Difficulty Calculator'],
    'equipment' => ['guide.equipment', 'Equipment Guide'],
    'guides' => ['guide.articles', 'Guides'],
    'account' => ['account', 'My Account'],
    'cart' => ['cart', 'Cart'],
];

foreach ($placeholders as $uri => [$name, $title]) {
    Route::view($uri, 'site.coming-soon', ['title' => $title])->name($name);
}
