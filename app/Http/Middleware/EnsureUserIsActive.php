<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Member area: an account deactivated by an organizer mid-session is logged out on its next request.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() !== null && ! $request->user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'This account has been deactivated. Message us on WhatsApp if you think this is a mistake.');
        }

        return $next($request);
    }
}
