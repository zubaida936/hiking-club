<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\Auth\ActivationController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\TokenController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\DifficultyController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\LeaderboardController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\RankSystemController;
use App\Http\Controllers\Api\V1\XEventsController;
use App\Http\Middleware\UseApiGuard;
use Illuminate\Support\Facades\Route;

/*
| API for the mobile app, under /api/v1 (docs: /docs/api). Controllers stay thin and call the same
| domain Actions as the website. Breaking changes go to a new version (/api/v2): installed apps
| keep calling the old one until members update.
*/

// UseApiGuard: $request->user() is the token's member (or null); `active` turns away deactivated accounts.
Route::prefix('v1')->name('api.v1.')->middleware([UseApiGuard::class, 'active', 'throttle:api'])->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('login', [TokenController::class, 'store'])->name('login');
        Route::post('activate', [ActivationController::class, 'store'])->middleware('throttle:5,1')->name('activate');
        Route::post('password/forgot', [PasswordResetController::class, 'send'])->middleware('throttle:3,1')->name('password.forgot');
        Route::post('password/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1')->name('password.reset');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [TokenController::class, 'destroy'])->name('logout');
            Route::post('logout-all', [TokenController::class, 'destroyAll'])->name('logout-all');
        });
    });

    Route::get('me', MeController::class)->middleware('auth:sanctum')->name('me');

    // My account (the website's member dashboard).
    Route::controller(AccountController::class)->prefix('me')->name('me.')->middleware('auth:sanctum')->group(function () {
        Route::get('bookings', 'bookings')->name('bookings');
        Route::get('points', 'points')->name('points');
        Route::get('rewards', 'rewards')->name('rewards');
        Route::put('leaderboard', 'leaderboard')->name('leaderboard');
    });

    Route::controller(EventController::class)->prefix('events')->name('events.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('past', 'past')->name('past');
        Route::get('months', 'months')->name('months');
        Route::get('{event:slug}', 'show')->name('show');
    });

    Route::get('leaderboard', LeaderboardController::class)->name('leaderboard');
    Route::get('rank-system', RankSystemController::class)->name('rank-system');
    Route::get('difficulty/levels', [DifficultyController::class, 'levels'])->name('difficulty.levels');
    Route::get('difficulty/calculate', [DifficultyController::class, 'calculate'])->name('difficulty.calculate');

    Route::controller(XEventsController::class)->prefix('x-events')->name('x-events.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('application-form', 'form')->name('application-form');
        Route::post('applications', 'apply')->middleware('throttle:5,1')->name('apply');
    });

    // Booking works for guests too (like the website); a token adds the member's details, rewards and X access.
    Route::controller(BookingController::class)->group(function () {
        Route::get('events/{event:slug}/booking-form', 'form')->name('events.booking-form');
        Route::post('events/{event:slug}/bookings', 'store')->middleware('throttle:5,1')->name('events.bookings.store');

        Route::prefix('bookings/{registration:token}')->name('bookings.')->group(function () {
            Route::get('/', 'show')->name('show');
            Route::post('pay', 'pay')->middleware('throttle:10,1')->name('pay');
            Route::post('transfer', 'transfer')->middleware('throttle:10,1')->name('transfer');
            Route::post('cancel', 'cancel')->middleware('auth:sanctum')->name('cancel');
        });
    });
});
