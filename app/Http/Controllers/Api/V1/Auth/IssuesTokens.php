<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Resources\Api\V1\MemberResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Every way into the app (login, activation, password reset) ends the same way: a new token for this device.
 */
trait IssuesTokens
{
    private function tokenResponse(User $user, string $deviceName): JsonResponse
    {
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return response()->json([
            'token' => $user->createToken($deviceName)->plainTextToken,
            'member' => MemberResource::forUser($user),
        ], 201);
    }
}
