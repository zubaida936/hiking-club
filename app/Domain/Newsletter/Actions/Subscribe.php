<?php

namespace App\Domain\Newsletter\Actions;

use App\Domain\Newsletter\Data\SubscriptionData;
use App\Mail\ConfirmSubscriptionMail;
use App\Models\Subscriber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Adds (or reactivates) a subscriber. Signing up twice is harmless: an
 * existing entry is reused and gains any missing contact detail, and an
 * unconfirmed email gets a fresh confirmation mail.
 */
final class Subscribe
{
    public function handle(SubscriptionData $data): Subscriber
    {
        $subscriber = DB::transaction(function () use ($data) {
            $byEmail = $data->email !== null ? Subscriber::where('email', $data->email)->lockForUpdate()->first() : null;
            $byPhone = $data->phone !== null ? Subscriber::where('phone', $data->phone)->lockForUpdate()->first() : null;

            $subscriber = $byEmail ?? $byPhone ?? new Subscriber(['token' => Str::random(64)]);

            if ($data->email !== null && $subscriber->email === null) {
                $subscriber->email = $data->email;
            }
            // Don't move a phone number that already belongs to another subscriber.
            if ($data->phone !== null && $subscriber->phone === null && $byPhone === null) {
                $subscriber->phone = $data->phone;
            }

            if (! $subscriber->isActive()) {
                $subscriber->unsubscribed_at = null;
                $subscriber->confirmed_at = null; // coming back means confirming the address again
            }

            $subscriber->save();

            return $subscriber;
        });

        if ($subscriber->awaitsConfirmation()) {
            Mail::to($subscriber->email)->queue(new ConfirmSubscriptionMail($subscriber));
        }

        return $subscriber;
    }
}
