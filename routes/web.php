<?php

use App\Http\Controllers\Site\AccountController;
use App\Http\Controllers\Site\Auth\ActivationController;
use App\Http\Controllers\Site\Auth\InviteController;
use App\Http\Controllers\Site\Auth\MemberSessionController;
use App\Http\Controllers\Site\Auth\PasswordResetController;
use App\Http\Controllers\Site\DifficultyCalculatorController;
use App\Http\Controllers\Site\EventController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\LeaderboardController;
use App\Http\Controllers\Site\NewsletterController;
use App\Http\Controllers\Site\PaymentController;
use App\Http\Controllers\Site\RegistrationController;
use App\Http\Controllers\Site\SeoController;
use App\Http\Controllers\Site\TestPaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('events', [EventController::class, 'index'])->name('events.index');
Route::get('leaderboard', LeaderboardController::class)->name('leaderboard');
Route::get('difficulty-calculator', DifficultyCalculatorController::class)->name('guide.difficulty');
Route::get('events/{event:slug}', [EventController::class, 'show'])->name('events.show');

Route::controller(RegistrationController::class)->group(function () {
    Route::get('events/{event:slug}/register', 'create')->name('events.register');
    Route::post('events/{event:slug}/register', 'store')->middleware('throttle:5,1')->name('events.register.store');
    Route::get('bookings/{registration:token}', 'show')->name('registrations.show');
});

Route::controller(PaymentController::class)->group(function () {
    Route::post('bookings/{registration:token}/pay', 'pay')->middleware('throttle:10,1')->name('registrations.pay');
    Route::post('bookings/{registration:token}/transfer', 'transfer')->middleware('throttle:10,1')->name('registrations.transfer');
    Route::get('payments/{payment:external_id}/return', 'return')->name('payments.return');
    // CSRF-exempt (bootstrap/app.php): called by the payment provider, not a browser.
    Route::post('payments/webhook/{gateway}', 'webhook')->middleware('throttle:60,1')->name('payments.webhook');
});

Route::controller(TestPaymentController::class)->prefix('payments/test/{payment:external_id}')->name('payments.test.')->group(function () {
    Route::get('/', 'show')->name('show');
    Route::post('/', 'simulate')->name('simulate');
});

// Member accounts: no open signup; hikers who booked activate an account, then log in with phone + password.
Route::middleware('guest')->group(function () {
    Route::get('login', [MemberSessionController::class, 'create'])->name('login');
    Route::post('login', [MemberSessionController::class, 'store'])->name('login.store');

    Route::controller(ActivationController::class)->prefix('activate')->name('activate')->group(function () {
        Route::get('/', 'create')->name('');
        Route::post('/', 'verify')->middleware('throttle:5,1')->name('.verify');
        Route::get('password', 'password')->name('.password');
        Route::post('password', 'store')->name('.store');
    });

    Route::controller(PasswordResetController::class)->name('password.')->group(function () {
        Route::get('forgot-password', 'create')->name('request');
        Route::post('forgot-password', 'send')->middleware('throttle:3,1')->name('email');
        Route::get('reset-password', 'edit')->name('reset');
        Route::post('reset-password', 'update')->middleware('throttle:10,1')->name('update');
    });
});

// Links organizers send from the CMS (signed, expiring): activate from an invite, choose a new password.
Route::controller(InviteController::class)->middleware(['signed', 'throttle:10,1'])->group(function () {
    Route::get('activate/invite/{hiker}', 'showActivation')->name('activate.invite');
    Route::post('activate/invite/{hiker}', 'activate')->name('activate.invite.store');
    Route::get('password/set/{user}', 'showPassword')->name('password.set');
    Route::post('password/set/{user}', 'setPassword')->name('password.set.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('account', [AccountController::class, 'show'])->name('account');
    Route::post('account/bookings/{registration}/cancel', [AccountController::class, 'cancelBooking'])->name('account.bookings.cancel');
    Route::post('account/leaderboard', [AccountController::class, 'leaderboardVisibility'])->name('account.leaderboard');
    Route::post('logout', [MemberSessionController::class, 'destroy'])->name('logout');
});

Route::get('sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('robots.txt', [SeoController::class, 'robots'])->name('robots');

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
    'x-events' => ['x-events', 'X Events'],
    'shop' => ['shop.index', 'Shop'],
    'rank-system' => ['rank-system', 'Rank System'],
    'contact' => ['contact', 'Contact'],
    'equipment' => ['guide.equipment', 'Equipment Guide'],
    'guides' => ['guide.articles', 'Guides'],
    'cart' => ['cart', 'Cart'],
    'privacy-policy' => ['legal.privacy', 'Privacy Policy'],
];

foreach ($placeholders as $uri => [$name, $title]) {
    Route::view($uri, 'site.coming-soon', ['title' => $title])->name($name);
}
