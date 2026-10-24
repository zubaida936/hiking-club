<?php

use App\Http\Controllers\Admin\AlbumController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\HeroSlideController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PointsController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Admin\StaffUserController;
use App\Http\Controllers\Admin\SubscriberController;
use App\Http\Controllers\Admin\TeamMemberController;
use App\Http\Controllers\Admin\XApplicationController;
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

    Route::resource('products', ProductController::class)->except('show');
    Route::controller(ProductCategoryController::class)->prefix('product-categories')->name('product-categories.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('{category}', 'update')->name('update');
        Route::delete('{category}', 'destroy')->name('destroy');
    });
    // {order:id}: the public order page uses the secret token as route key.
    Route::controller(OrderController::class)->prefix('orders')->name('orders.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('{order:id}', 'show')->name('show');
        Route::post('{order:id}/status', 'status')->name('status');
        Route::post('{order:id}/cancel', 'cancel')->name('cancel');
        Route::post('{order:id}/payment', 'payment')->name('payment');
    });

    // Content: guide articles, gallery, homepage hero, team, newsletter subscribers.
    Route::resource('articles', ArticleController::class)->except('show');
    Route::resource('albums', AlbumController::class)->except('show');
    Route::resource('hero-slides', HeroSlideController::class)->except('show');
    Route::resource('team', TeamMemberController::class)->except('show')->parameters(['team' => 'member']);
    Route::controller(SubscriberController::class)->prefix('subscribers')->name('subscribers.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('export', 'export')->name('export');
        Route::post('{subscriber:id}/unsubscribe', 'unsubscribe')->name('unsubscribe');
        Route::delete('{subscriber:id}', 'destroy')->name('destroy');
    });

    Route::controller(XApplicationController::class)->prefix('x-applications')->name('x-applications.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('{application}', 'show')->name('show');
        Route::post('{application}/approve', 'approve')->name('approve');
        Route::post('{application}/reject', 'reject')->name('reject');
        Route::post('{application}/revoke', 'revoke')->name('revoke');
        Route::post('{application}/note', 'note')->name('note');
        Route::post('{application}/access-link', 'accessLink')->name('access-link');
    });

    Route::controller(MemberController::class)->prefix('members')->name('members.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('rewards', 'rewards')->name('rewards');
        Route::post('{hiker}/invite', 'invite')->name('invite');
        Route::post('{hiker}/password-link', 'passwordLink')->name('password-link');
        Route::post('{hiker}/deactivate', 'deactivate')->name('deactivate');
        Route::post('{hiker}/reactivate', 'reactivate')->name('reactivate');
    });

    Route::controller(PointsController::class)->prefix('points')->name('points.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('{hiker}', 'show')->name('show');
        Route::post('{hiker}/adjust', 'adjust')->name('adjust');
        Route::post('{hiker}/rewards', 'handOver')->name('rewards.hand-over');
    });

    Route::controller(RegistrationController::class)->group(function () {
        Route::get('registrations', 'index')->name('registrations.index');
        Route::get('events/{event}/registrations', 'show')->name('events.registrations');
        Route::get('events/{event}/registrations/export', 'export')->name('events.registrations.export');
        Route::post('events/{event}/updates', 'postUpdate')->name('events.updates.store');

        Route::prefix('registrations/{registration}')->name('registrations.')->group(function () {
            Route::get('edit', 'edit')->name('edit');
            Route::put('/', 'update')->name('update');
            Route::post('approve', 'approve')->name('approve');
            Route::post('cancel', 'cancel')->name('cancel');
            Route::post('payment', 'payment')->name('payment');
            Route::post('refunded', 'refunded')->name('refunded');
            Route::post('attendance', 'attendance')->name('attendance');
        });
    });
});
