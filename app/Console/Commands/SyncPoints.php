<?php

namespace App\Console\Commands;

use App\Domain\Loyalty\Actions\SyncRegistrationPoints;
use App\Domain\Members\Actions\ResolveHiker;
use App\Models\EventRegistration;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('points:sync')]
#[Description('Link bookings to hikers and bring every points ledger up to date (run after changing config/points.php)')]
class SyncPoints extends Command
{
    public function handle(ResolveHiker $resolveHiker, SyncRegistrationPoints $sync): int
    {
        $linked = 0;
        EventRegistration::whereNull('hiker_id')->lazyById()->each(function (EventRegistration $registration) use ($resolveHiker, &$linked) {
            $registration->update(['hiker_id' => $resolveHiker->handle($registration->phone, $registration->name, $registration->email)->id]);
            $linked++;
        });

        $lines = 0;
        EventRegistration::query()
            ->where(fn ($q) => $q->whereNotNull('attendance')->orWhereHas('pointTransactions'))
            ->lazyById()
            ->each(function (EventRegistration $registration) use ($sync, &$lines) {
                $lines += $sync->handle($registration);
            });

        $this->components->info("Linked {$linked} booking(s) to hikers; added {$lines} ledger line(s).");

        return self::SUCCESS;
    }
}
