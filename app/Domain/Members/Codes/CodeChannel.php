<?php

namespace App\Domain\Members\Codes;

use App\Models\User;

/**
 * Delivers a one-time code to a member (email today; SMS/WhatsApp once a provider is chosen).
 */
interface CodeChannel
{
    /**
     * Whether this member can be reached through the channel (e.g. has an email).
     */
    public function canReach(User $user): bool;

    public function send(User $user, string $code): void;
}
