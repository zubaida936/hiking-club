<?php

namespace App\Console\Commands;

use App\Domain\Events\Difficulty\DifficultyCalculator;
use App\Models\Event;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('events:recalculate-difficulty')]
#[Description('Recalculate effort score and level of every event (run after changing config/difficulty.php)')]
class RecalculateEventDifficulty extends Command
{
    public function handle(DifficultyCalculator $calculator): int
    {
        $changed = 0;

        Event::withTrashed()->lazyById()->each(function (Event $event) use ($calculator, &$changed) {
            $d = $calculator->calculate($event->distance_km, $event->elevation_gain_m, $event->elevation_loss_m);

            $event->effort_score = $d->effortScore;
            $event->difficulty_level = $d->level;

            if ($event->isDirty()) {
                $event->saveQuietly();
                $changed++;
            }
        });

        $this->components->info("Updated {$changed} event(s).");

        return self::SUCCESS;
    }
}
