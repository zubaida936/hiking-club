<?php

namespace App\Console\Commands;

use App\Domain\Registrations\Actions\SendEventReminders as SendReminders;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('events:send-reminders')]
#[Description('Email booked hikers a reminder before their hike (scheduled every hour; config site.booking.reminder_*)')]
class SendEventReminders extends Command
{
    public function handle(SendReminders $reminders): int
    {
        $this->components->info("Queued {$reminders->handle()} reminder(s).");

        return self::SUCCESS;
    }
}
