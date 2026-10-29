<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\TokenLoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * App login with phone + password. Each device gets its own token, sent back as `Authorization: Bearer <token>`.
 */
class TokenController extends Controller
{
    use IssuesTokens;

    /**
     * Log in.
     *
     * Same rules as the website: no open signup, deactivated accounts can't log in, 5 wrong tries lock the
     * number for a minute. The error never tells whether the number has an account.
     *
     * @unauthenticated
     */
    public function store(TokenLoginRequest $request): JsonResponse
    {
        $request->authenticate();

        return $this->tokenResponse($request->member(), $request->string('device_name')->toString());
    }

    /**
     * Log out this device.
     */
    public function destroy(Request $request): Response
    {
        /** @var PersonalAccessToken $token */
        $token = $request->user()?->currentAccessToken();
        $token->delete();

        return response()->noContent();
    }

    /**
     * Log out every device.
     */
    public function destroyAll(Request $request): Response
    {
        $request->user()?->tokens()->delete();

        return response()->noContent();
    }
}
