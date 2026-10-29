<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Member area: an account deactivated by an organizer mid-session is logged out on its next request.
 * In the app (API), its tokens are revoked and the app gets a 401 to send the member back to login.
 */
class EnsureUserIsActive
{
    public const DEACTIVATED_MESSAGE = 'This account has been deactivated. Message us on WhatsApp if you think this is a mistake.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->is_active) {
            if ($request->is('api/*')) {
                $user->tokens()->delete();

                return response()->json(['message' => self::DEACTIVATED_MESSAGE], 401);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', self::DEACTIVATED_MESSAGE);
        }

        return $next($request);
    }
}
