<?php

namespace App\Domain\Members\Codes;

use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

final class MailCodeChannel implements CodeChannel
{
    public function canReach(User $user): bool
    {
        return $user->email !== null;
    }

    public function send(User $user, string $code): void
    {
        // Sent right away (not queued): the member is waiting on the code screen.
        Mail::to((string) $user->email)->send(new PasswordResetCodeMail($user, $code));
    }
}
