<?php

namespace App\Domain\Members\Codes;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Development only: writes the code to the application log.
 */
final class LogCodeChannel implements CodeChannel
{
    public function canReach(User $user): bool
    {
        return true;
    }

    public function send(User $user, string $code): void
    {
        Log::info("Password reset code for {$user->phone}: {$code}");
    }
}
