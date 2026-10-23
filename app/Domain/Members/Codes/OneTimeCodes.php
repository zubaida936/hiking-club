<?php

namespace App\Domain\Members\Codes;

use App\Models\OneTimeCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * 6-digit codes for a member: issue one (replacing earlier ones for the same
 * purpose) and check it. Codes expire and lock after too many wrong tries.
 */
final class OneTimeCodes
{
    public const PASSWORD_RESET = 'password_reset';

    public function __construct(private readonly CodeChannel $channel) {}

    public function canReach(User $user): bool
    {
        return $this->channel->canReach($user);
    }

    public function issue(User $user, string $purpose): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OneTimeCode::where('user_id', $user->id)->where('purpose', $purpose)->whereNull('used_at')->delete();
        OneTimeCode::create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes((int) config('members.code_ttl_minutes')),
        ]);

        $this->channel->send($user, $code);
    }

    /**
     * Checks the code and, when right, uses it up. Every wrong try counts.
     */
    public function consume(User $user, string $purpose, string $code): bool
    {
        $record = OneTimeCode::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($record === null || $record->attempts >= (int) config('members.code_max_attempts')) {
            return false;
        }

        if (! Hash::check($code, $record->code_hash)) {
            $record->increment('attempts');

            return false;
        }

        $record->update(['used_at' => now()]);

        return true;
    }
}
