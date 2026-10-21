<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards every CMS route. A user deactivated (or stripped of CMS access)
 * mid-session is logged out on their next request.
 */
class EnsureCanAccessAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->canAccessAdmin()) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
