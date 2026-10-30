<?php

use App\Http\Controllers\Site\AboutController;
use App\Http\Controllers\Site\AccountController;
use App\Http\Controllers\Site\AppLinksController;
use App\Http\Controllers\Site\Auth\ActivationController;
use App\Http\Controllers\Site\Auth\InviteController;
use App\Http\Controllers\Site\Auth\MemberSessionController;
use App\Http\Controllers\Site\Auth\PasswordResetController;
use App\Http\Controllers\Site\CartController;
use App\Http\Controllers\Site\CheckoutController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\DifficultyCalculatorController;
use App\Http\Controllers\Site\EquipmentController;
use App\Http\Controllers\Site\EventController;
use App\Http\Controllers\Site\GalleryController;
use App\Http\Controllers\Site\GuideController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\LeaderboardController;
use App\Http\Controllers\Site\NewsletterController;
use App\Http\Controllers\Site\PaymentController;
use App\Http\Controllers\Site\RankSystemController;
use App\Http\Controllers\Site\RegistrationController;
use App\Http\Controllers\Site\SeoController;
use App\Http\Controllers\Site\ShopController;
use App\Http\Controllers\Site\TestPaymentController;
use App\Http\Controllers\Site\XEventsController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// App Links / Universal Links: phones check these before opening website links in the installed app.
Route::get('.well-known/assetlinks.json', [AppLinksController::class, 'android'])->name('app-links.android');
Route::get('.well-known/apple-app-site-association', [AppLinksController::class, 'apple'])->name('app-links.apple');

Route::get('events', [EventController::class, 'index'])->name('events.index');
Route::get('events/archive', [EventController::class, 'archive'])->name('events.archive');
Route::get('leaderboard', LeaderboardController::class)->name('leaderboard');
Route::get('rank-system', RankSystemController::class)->name('rank-system');
Route::get('difficulty-calculator', DifficultyCalculatorController::class)->name('guide.difficulty');

Route::get('about', AboutController::class)->name('about');
Route::get('guides', [GuideController::class, 'index'])->name('guide.articles');
Route::get('guides/{article:slug}', [GuideController::class, 'show'])->name('guide.articles.show');
Route::get('gallery', [GalleryController::class, 'index'])->name('gallery.index');
Route::get('gallery/{album:slug}', [GalleryController::class, 'show'])->name('gallery.show');
Route::get('equipment', EquipmentController::class)->name('guide.equipment');
Route::get('contact', [ContactController::class, 'show'])->name('contact');
Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.send');

Route::get('shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('shop/{product:slug}', [ShopController::class, 'show'])->name('shop.show');

Route::controller(CartController::class)->group(function () {
    Route::get('cart', 'show')->name('cart');
    Route::post('cart/add', 'add')->middleware('throttle:30,1')->name('cart.add');
    Route::post('cart', 'update')->name('cart.update');
    Route::post('cart/remove/{variant}', 'remove')->name('cart.remove');
});

Route::controller(CheckoutController::class)->group(function () {
    Route::get('checkout', 'create')->name('checkout');
    Route::post('checkout', 'store')->middleware('throttle:5,1')->name('checkout.store');
    Route::get('orders/{order}', 'show')->name('orders.show');
    Route::post('orders/{order}/pay', 'pay')->middleware('throttle:10,1')->name('orders.pay');
    Route::post('orders/{order}/transfer', 'transfer')->middleware('throttle:10,1')->name('orders.transfer');
});

Route::controller(XEventsController::class)->prefix('x-events')->group(function () {
    Route::get('/', 'index')->name('x-events');
    Route::get('apply', 'create')->name('x-events.apply');
    Route::post('apply', 'store')->middleware('throttle:5,1')->name('x-events.apply.store');
    Route::get('apply/sent', 'applied')->name('x-events.applied');
});
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
    Route::get('account/profile', [AccountController::class, 'editProfile'])->name('account.profile');
    Route::post('account/profile', [AccountController::class, 'updateProfile'])->middleware('throttle:10,1')->name('account.profile.update');
    Route::get('account/delete', [AccountController::class, 'confirmDelete'])->name('account.delete');
    Route::post('account/delete', [AccountController::class, 'destroy'])->middleware('throttle:5,1')->name('account.delete.destroy');
    Route::post('logout', [MemberSessionController::class, 'destroy'])->name('logout');
});

// Public: how to delete an account and what happens to the data (Google Play asks for this page).
Route::view('delete-account', 'site.delete-account')->name('delete-account');

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
    'privacy-policy' => ['legal.privacy', 'Privacy Policy'],
];

foreach ($placeholders as $uri => [$name, $title]) {
    Route::view($uri, 'site.coming-soon', ['title' => $title])->name($name);
}
