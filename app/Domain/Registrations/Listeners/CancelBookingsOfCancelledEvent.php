<?php

namespace App\Domain\Registrations\Listeners;

use App\Domain\Events\Events\EventCancelled;
use App\Domain\Registrations\Actions\CancelRegistration;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Models\EventRegistration;

/**
 * A cancelled hike cancels its bookings and waitlist entries: each hiker is emailed
 * the reason, paid bookings become "Refund due" and used rewards are given back.
 * Runs right away so the organizer sees the result when the page reloads.
 */
final class CancelBookingsOfCancelledEvent
{
    public function __construct(private readonly CancelRegistration $cancel) {}

    public function handle(EventCancelled $cancelled): void
    {
        $reason = 'The hike was cancelled'.($cancelled->event->cancellation_reason ? ': '.$cancelled->event->cancellation_reason : '.');

        EventRegistration::query()
            ->whereBelongsTo($cancelled->event)
            ->where('status', '!=', RegistrationStatus::Cancelled)
            ->with('event')
            ->lazyById()
            ->each(fn (EventRegistration $registration) => $this->cancel->handle($registration, $reason, $cancelled->actor));
    }
}
