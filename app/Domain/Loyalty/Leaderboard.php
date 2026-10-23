<?php

namespace App\Domain\Loyalty;

use App\Domain\Registrations\Enums\Attendance;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Models\EventRegistration;
use App\Models\Hiker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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
     * The public leaderboard: hikers with points who haven't hidden themselves, in rank order.
     *
     * @return Builder<Hiker>
     */
    public function publicQuery(): Builder
    {
        return $this->query()
            ->where('hikers.show_on_leaderboard', true)
            ->having('total_points', '>', 0);
    }

    /**
     * @return Collection<int, Hiker>
     */
    public function top(int $limit): Collection
    {
        return $this->publicQuery()->limit($limit)->get();
    }

    /**
     * A hiker's place on the public leaderboard; null when not listed (hidden or no points).
     */
    public function publicPositionOf(Hiker $hiker): ?int
    {
        $ids = $this->publicQuery()->toBase()->get()->values()->map(fn (object $row) => (int) $row->id);
        $index = $ids->search($hiker->id);

        return $index === false ? null : $index + 1;
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
