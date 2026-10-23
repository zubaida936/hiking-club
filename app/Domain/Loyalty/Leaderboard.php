<?php

namespace App\Domain\Loyalty;

use App\Domain\Registrations\Enums\Attendance;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Models\EventRegistration;
use App\Models\Hiker;
use Illuminate\Database\Eloquent\Builder;

/**
 * Hikers ranked by points, ties broken by total distance hiked (then name).
 */
final class Leaderboard
{
    /**
     * Hikers with total_points, hikes_completed and total_distance_km, in rank order.
     *
     * @return Builder<Hiker>
     */
    public function query(): Builder
    {
        return Hiker::query()
            ->select('hikers.*')
            ->withPointTotals()
            ->addSelect(['total_distance_km' => EventRegistration::query()
                ->join('events', 'events.id', '=', 'event_registrations.event_id')
                ->whereColumn('event_registrations.hiker_id', 'hikers.id')
                ->where('event_registrations.status', RegistrationStatus::Confirmed)
                ->where('event_registrations.attendance', Attendance::Attended)
                ->selectRaw('coalesce(sum(events.distance_km), 0)')])
            ->orderByDesc('total_points')
            ->orderByDesc('total_distance_km')
            ->orderBy('name')
            ->orderBy('id');
    }

    /**
     * Every hiker's 1-based position.
     *
     * @return array<int, int> hiker id => position
     */
    public function positions(): array
    {
        // get() rather than pluck(): the ordering needs the computed total columns selected.
        return $this->query()->toBase()->get()
            ->values()
            ->mapWithKeys(fn (object $row, int $index) => [(int) $row->id => $index + 1])
            ->all();
    }

    public function positionOf(Hiker $hiker): int
    {
        return $this->positions()[$hiker->id] ?? 0;
    }
}
