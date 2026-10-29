<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * API requests: $request->user() is the member of the bearer token (Sanctum), or null for guests.
 * Endpoints open to guests (booking a hike) still know the member when the app sends a token,
 * the same way the website knows a logged-in member. Routes needing a login add auth:sanctum.
 */
class UseApiGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('sanctum');

        return $next($request);
    }
}
