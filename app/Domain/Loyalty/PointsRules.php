<?php

namespace App\Domain\Loyalty;

use App\Domain\Loyalty\Enums\PointType;
use App\Domain\Registrations\Enums\Attendance;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use Carbon\CarbonImmutable;

/**
 * What a booking is worth, from config/points.php. Only confirmed bookings marked
 * as attended earn points, and (when paid_only) only once paid unless the hike is free.
 */
final class PointsRules
{
    /**
     * The points this booking should have produced, per hiker and type.
     *
     * @return list<array{hiker_id: int, type: PointType, points: int, description: string}>
     */
    public function for(EventRegistration $registration): array
    {
        $event = $registration->event;

        if (! $this->counts($registration, $event)) {
            return [];
        }

        /** @var int $hikerId */
        $hikerId = $registration->hiker_id;
        $earned = [[
            'hiker_id' => $hikerId,
            'type' => PointType::Hike,
            'points' => $this->hikePoints($event),
            'description' => "Completed {$event->title} ({$event->difficulty_level->label()})",
        ]];

        if ($this->bookedEarly($registration, $event)) {
            $earned[] = [
                'hiker_id' => $hikerId,
                'type' => PointType::EarlyBooking,
                'points' => (int) config('points.early_booking.points'),
                'description' => 'Booked '.config('points.early_booking.days')."+ days ahead: {$event->title}",
            ];
        }

        $referrer = $registration->referred_by_hiker_id;
        if ($referrer !== null && $referrer !== $hikerId && $this->isFirstCompletedHike($registration)) {
            $earned[] = [
                'hiker_id' => $referrer,
                'type' => PointType::Referral,
                'points' => (int) config('points.referral'),
                'description' => "Brought a new hiker: {$registration->name} ({$event->title})",
            ];
        }

        return $earned;
    }

    public function hikePoints(Event $event): int
    {
        return (int) config("points.hike.{$event->difficulty_level->value}", 0);
    }

    /**
     * Whether a booking made now would get the early-booking bonus.
     */
    public function earlyBookingOpen(Event $event): bool
    {
        return $this->bookedEarlyAt(CarbonImmutable::now(), $event);
    }

    private function counts(EventRegistration $registration, Event $event): bool
    {
        return $registration->hiker_id !== null
            && $registration->status === RegistrationStatus::Confirmed
            && $registration->attendance === Attendance::Attended
            && (! config('points.paid_only') || $event->fee_cents === 0 || $registration->paid_at !== null);
    }

    private function bookedEarly(EventRegistration $registration, Event $event): bool
    {
        return $this->bookedEarlyAt($registration->created_at->toImmutable(), $event);
    }

    /**
     * Booked at least N calendar days before the hike day, in club time.
     */
    private function bookedEarlyAt(CarbonImmutable $bookedAt, Event $event): bool
    {
        $bookedOn = $bookedAt->setTimezone(config('site.timezone'))->startOfDay();
        $hikeDay = CarbonImmutable::parse($event->starts_on->toDateString(), config('site.timezone'));

        return $bookedOn->diffInDays($hikeDay, false) >= (int) config('points.early_booking.days');
    }

    /**
     * The hiker's earliest completed hike (by hike date), so a referral pays out exactly once.
     */
    private function isFirstCompletedHike(EventRegistration $registration): bool
    {
        $first = EventRegistration::query()
            ->join('events', 'events.id', '=', 'event_registrations.event_id')
            ->where('event_registrations.hiker_id', $registration->hiker_id)
            ->where('event_registrations.status', RegistrationStatus::Confirmed)
            ->where('event_registrations.attendance', Attendance::Attended)
            ->orderBy('events.starts_on')
            ->orderBy('event_registrations.id')
            ->value('event_registrations.id');

        return $first === $registration->id;
    }
}
