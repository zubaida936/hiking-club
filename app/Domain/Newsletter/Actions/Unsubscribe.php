<?php

namespace App\Domain\Newsletter\Actions;

use App\Models\Subscriber;

final class Unsubscribe
{
    public function handle(Subscriber $subscriber): Subscriber
    {
        if ($subscriber->isActive()) {
            $subscriber->update(['unsubscribed_at' => now()]);
        }

        return $subscriber;
    }
}
