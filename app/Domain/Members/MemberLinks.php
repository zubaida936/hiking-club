<?php

namespace App\Domain\Members;

use App\Models\Hiker;
use App\Models\User;
use Illuminate\Support\Facades\URL;

/**
 * Links organizers send to hikers (usually on WhatsApp): an invite to activate an account
 * without a booking reference, and a link to choose a new password. Both are signed and expire.
 */
final class MemberLinks
{
    public const INVITE_DAYS = 7;

    public const PASSWORD_HOURS = 24;

    public function activation(Hiker $hiker): string
    {
        return URL::temporarySignedRoute('activate.invite', now()->addDays(self::INVITE_DAYS), ['hiker' => $hiker->id]);
    }

    /**
     * Works once: the fingerprint of the current password is part of the signed link.
     */
    public function password(User $user): string
    {
        return URL::temporarySignedRoute('password.set', now()->addHours(self::PASSWORD_HOURS), [
            'user' => $user->id,
            'v' => $this->fingerprint($user),
        ]);
    }

    public function passwordLinkIsCurrent(User $user, string $fingerprint): bool
    {
        return hash_equals($this->fingerprint($user), $fingerprint);
    }

    private function fingerprint(User $user): string
    {
        return substr(hash_hmac('sha256', (string) $user->getAuthPassword(), (string) config('app.key')), 0, 16);
    }
}
