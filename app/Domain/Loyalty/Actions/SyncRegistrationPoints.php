<?php

namespace App\Domain\Loyalty\Actions;

use App\Domain\Loyalty\Enums\PointType;
use App\Domain\Loyalty\PointsRules;
use App\Models\EventRegistration;
use App\Models\PointTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Brings the ledger in line with what a booking is worth now: adds the points
 * it earned, or adds reversing lines when it no longer qualifies (attendance
 * cleared, cancelled, marked unpaid, points config changed). Safe to run any
 * number of times; existing lines are never edited.
 */
final class SyncRegistrationPoints
{
    public function __construct(private readonly PointsRules $rules) {}

    /**
     * @return int number of ledger lines added
     */
    public function handle(EventRegistration $registration): int
    {
        return DB::transaction(function () use ($registration) {
            // Serialize syncs of the same booking (double clicks, queue + request).
            /** @var EventRegistration $registration */
            $registration = EventRegistration::whereKey($registration->id)->lockForUpdate()->firstOrFail();

            $desired = collect($this->rules->for($registration))
                ->keyBy(fn (array $line) => $line['hiker_id'].'|'.$line['type']->value);

            $current = $registration->pointTransactions()
                ->whereIn('type', PointType::automatic())
                ->selectRaw('hiker_id, type, sum(points) as total')
                ->groupBy('hiker_id', 'type')
                ->toBase()
                ->get()
                ->keyBy(fn ($row) => $row->hiker_id.'|'.$row->type);

            $added = 0;

            foreach ($desired->keys()->merge($current->keys())->unique() as $key) {
                $want = $desired->get($key);
                $have = (int) ($current->get($key)->total ?? 0);
                $diff = ($want['points'] ?? 0) - $have;

                if ($diff === 0) {
                    continue;
                }

                [$hikerId, $type] = explode('|', $key);
                $type = PointType::from($type);

                PointTransaction::create([
                    'hiker_id' => (int) $hikerId,
                    'event_registration_id' => $registration->id,
                    'type' => $type,
                    'points' => $diff,
                    'description' => match (true) {
                        $want === null => "Reversed: {$type->label()} ({$registration->event->title})",
                        $have === 0 => $want['description'],
                        default => "Updated: {$want['description']}",
                    },
                ]);
                $added++;
            }

            return $added;
        });
    }
}
