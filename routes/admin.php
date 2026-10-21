<?php

use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\StaffUserController;
use Illuminate\Support\Facades\Route;

/*
| CMS routes. Loaded from bootstrap/app.php with the "admin." name prefix
| and the configurable URL prefix (config/cms.php -> path).
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('staff', StaffUserController::class)
        ->except('show')
        ->parameters(['staff' => 'user']);

    Route::resource('events', EventController::class)->except('show');
    Route::controller(EventController::class)->prefix('events/{event}')->name('events.')->group(function () {
        Route::post('publish', 'publish')->name('publish');
        Route::post('unpublish', 'unpublish')->name('unpublish');
        Route::post('cancel', 'cancel')->name('cancel');
        Route::post('duplicate', 'duplicate')->name('duplicate');
    });
});
