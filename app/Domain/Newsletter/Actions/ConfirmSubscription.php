<?php

namespace App\Domain\Newsletter\Actions;

use App\Models\Subscriber;

final class ConfirmSubscription
{
    public function handle(Subscriber $subscriber): Subscriber
    {
        if ($subscriber->confirmed_at === null) {
            $subscriber->update(['confirmed_at' => now(), 'unsubscribed_at' => null]);
        }

        return $subscriber;
    }
}
