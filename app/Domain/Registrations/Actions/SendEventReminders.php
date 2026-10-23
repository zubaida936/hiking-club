<?php

namespace App\Domain\Registrations\Actions;

use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Mail\EventReminderMail;
use App\Models\Event;
use App\Models\EventRegistration;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;

/**
 * Emails confirmed hikers a reminder (pickup point and time, what to bring, payment due,
 * latest updates) `reminder_days_before` days before the hike, from `reminder_hour`
 * (config site.booking, Lebanon time). Runs every hour; each booking gets it once.
 */
final class SendEventReminders
{
    /**
     * @return int reminders queued
     */
    public function handle(?CarbonImmutable $now = null): int
    {
        $local = ($now ?? CarbonImmutable::now())->setTimezone(config('site.timezone'));

        if ($local->hour < (int) config('site.booking.reminder_hour')) {
            return 0;
        }

        $hikeDay = $local->addDays((int) config('site.booking.reminder_days_before'))->toDateString();
        $sent = 0;

        Event::published()->whereDate('starts_on', $hikeDay)->each(function (Event $event) use (&$sent) {
            EventRegistration::query()
                ->whereBelongsTo($event)
                ->where('status', RegistrationStatus::Confirmed)
                ->whereNotNull('email')
                ->whereNull('reminder_sent_at')
                ->with('event')
                ->lazyById()
                ->each(function (EventRegistration $registration) use (&$sent) {
                    Mail::to((string) $registration->email)->queue(new EventReminderMail($registration));
                    $registration->update(['reminder_sent_at' => now()]);
                    $sent++;
                });
        });

        return $sent;
    }
}
