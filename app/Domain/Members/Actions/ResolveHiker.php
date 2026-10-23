<?php

namespace App\Domain\Members\Actions;

use App\Models\Hiker;

/**
 * The hiker behind a (normalized) phone number, created on their first booking.
 * Keeps the name from the latest booking and the latest email given.
 */
final class ResolveHiker
{
    public function handle(string $phone, string $name, ?string $email): Hiker
    {
        // createOrFirst: two bookings arriving together can't create the same hiker twice.
        $hiker = Hiker::createOrFirst(['phone' => $phone], ['name' => $name, 'email' => $email]);

        $hiker->fill(array_filter(['name' => $name, 'email' => $email]))->save();

        return $hiker;
    }
}
