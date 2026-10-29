<?php

namespace App\Domain\Members\Actions;

use App\Domain\Members\Codes\OneTimeCodes;
use App\Domain\Members\Exceptions\MemberException;
use App\Models\User;

/**
 * Sets a new password once the member gives the right reset code.
 */
final class ResetPassword
{
    public function __construct(private readonly OneTimeCodes $codes) {}

    /**
     * @throws MemberException
     */
    public function handle(User $user, string $code, string $password): void
    {
        if (! $this->codes->consume($user, OneTimeCodes::PASSWORD_RESET, $code)) {
            throw MemberException::invalidCode();
        }

        // Clearing the remember token signs out "remember me" sessions on other devices; the app logins too.
        $user->forceFill(['password' => $password, 'remember_token' => null])->save();
        $user->tokens()->delete();
    }
}
