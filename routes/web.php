<?php

use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\NewsletterController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::controller(NewsletterController::class)->prefix('newsletter')->name('newsletter.')->group(function () {
    Route::post('/', 'store')->middleware('throttle:5,1')->name('subscribe');
    Route::get('confirm/{subscriber:token}', 'confirm')->name('confirm');
    Route::get('unsubscribe/{subscriber:token}', 'showUnsubscribe')->name('unsubscribe');
    Route::post('unsubscribe/{subscriber:token}', 'unsubscribe')->name('unsubscribe.confirm');
});

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
    'privacy-policy' => ['legal.privacy', 'Privacy Policy'],
];

foreach ($placeholders as $uri => [$name, $title]) {
    Route::view($uri, 'site.coming-soon', ['title' => $title])->name($name);
}
