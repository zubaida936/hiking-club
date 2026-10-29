<?php

use App\Http\Middleware\EnsureCanAccessAdmin;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
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
        // The site always runs behind a proxy (Cloudflare, Codespaces, a load balancer): build links from its
        // X-Forwarded-* headers so they keep the public https host instead of http://localhost.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => EnsureCanAccessAdmin::class,
            'active' => EnsureUserIsActive::class,
        ]);

        // Payment providers post here server-to-server; the payment status is re-checked with them.
        $middleware->validateCsrfTokens(except: ['payments/webhook/*']);

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

        // Domain rule violations (App\Domain\*\Exceptions) carry user-safe messages: the app shows them as they are.
        $exceptions->render(function (DomainException $e, Request $request) {
            if ($request->is('api/*') && str_starts_with($e::class, 'App\\Domain\\')) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return null;
        });
    })->create();
