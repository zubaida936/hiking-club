<?php

namespace App\Console\Commands;

use App\Domain\Loyalty\Actions\SyncRegistrationPoints;
use App\Domain\Members\Actions\ResolveHiker;
use App\Domain\Rewards\Actions\GrantRankBonuses;
use App\Models\EventRegistration;
use App\Models\Hiker;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('points:sync')]
#[Description('Link bookings to hikers and bring every points ledger up to date (run after changing config/points.php)')]
class SyncPoints extends Command
{
    public function handle(ResolveHiker $resolveHiker, SyncRegistrationPoints $sync, GrantRankBonuses $bonuses): int
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

        // Rank bonuses (config/rewards.php) unlocked by the new totals.
        Hiker::query()->whereHas('pointTransactions')->lazyById()->each(fn (Hiker $hiker) => $bonuses->handle($hiker));

        $this->components->info("Linked {$linked} booking(s) to hikers; added {$lines} ledger line(s).");

        return self::SUCCESS;
    }
}
