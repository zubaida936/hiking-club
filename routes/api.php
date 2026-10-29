<?php

use App\Http\Controllers\Api\V1\Auth\ActivationController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\TokenController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\MeController;
use Illuminate\Support\Facades\Route;

/*
| API for the mobile app, under /api/v1 (docs: /docs/api). Controllers stay thin and call the same
| domain Actions as the website. Breaking changes go to a new version (/api/v2): installed apps
| keep calling the old one until members update.
*/

Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('login', [TokenController::class, 'store'])->name('login');
        Route::post('activate', [ActivationController::class, 'store'])->middleware('throttle:5,1')->name('activate');
        Route::post('password/forgot', [PasswordResetController::class, 'send'])->middleware('throttle:3,1')->name('password.forgot');
        Route::post('password/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1')->name('password.reset');

        Route::middleware(['auth:sanctum', 'active'])->group(function () {
            Route::post('logout', [TokenController::class, 'destroy'])->name('logout');
            Route::post('logout-all', [TokenController::class, 'destroyAll'])->name('logout-all');
        });
    });

    Route::get('me', MeController::class)->middleware(['auth:sanctum', 'active'])->name('me');

    Route::controller(EventController::class)->prefix('events')->name('events.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('past', 'past')->name('past');
        Route::get('months', 'months')->name('months');
        Route::get('{event:slug}', 'show')->name('show');
    });
});
