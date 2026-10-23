<?php

use App\Http\Middleware\EnsureCanAccessAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->prefix(config('cms.path'))
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureCanAccessAdmin::class,
        ]);

        // CMS pages send guests to the staff login; the public site to the member login.
        $isCms = fn (Request $request) => $request->is(trim((string) config('cms.path'), '/'), trim((string) config('cms.path'), '/').'/*');
        $middleware->redirectGuestsTo(fn (Request $request) => $isCms($request) ? route('admin.login') : route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $isCms($request) && $request->user()?->canAccessAdmin()
            ? route('admin.dashboard')
            : route('account'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
