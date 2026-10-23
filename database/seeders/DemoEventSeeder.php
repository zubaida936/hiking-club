<?php

namespace Database\Seeders;

use App\Domain\Events\Difficulty\DifficultyCalculator;
use App\Domain\Events\Enums\EventStatus;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Realistic sample events for local development and manual testing.
 * Not run in production (see DatabaseSeeder).
 */
class DemoEventSeeder extends Seeder
{
    public function run(DifficultyCalculator $calculator): void
    {
        $pickups = [
            ['name' => 'Sour', 'pickup_time' => '05:50'],
            ['name' => 'Saida', 'pickup_time' => '06:40'],
            ['name' => 'Beirut - Jnah', 'pickup_time' => '07:30'],
            ['name' => 'Beirut - Downtown', 'pickup_time' => '07:40'],
        ];

        /** @var list<array{string, string, int, float, int, int, int, ?string, EventStatus, bool}> $events */
        $events = [
            ['Horch Ehden Nature Reserve', 'Ehden', 10, 8.0, 220, 320, 3000, '🍂 Autumn Trail', EventStatus::Published, false],
            ['Qadisha Valley Descent', 'Bcharre', 24, 14.5, 450, 1100, 3500, '🍂 Autumn Trail', EventStatus::Draft, false],
            ['X30 Tannourine to Douma', 'Tannourine', 38, 30.0, 1500, 1700, 4500, null, EventStatus::Published, true],
            ['Baadaran Airport to Bisri', 'Chouf', -4, 17.0, 350, 800, 3000, '🍂 Autumn Trail', EventStatus::Published, false],
            ['X27 Tannourine - Batroun', 'Batroun', -18, 27.0, 900, 1900, 4000, null, EventStatus::Published, true],
            ['Hadath El Jebbe to Tannourine el Tahta', 'Hadath El Jebbe', -32, 13.0, 400, 900, 3000, null, EventStatus::Cancelled, false],
        ];

        foreach ($events as [$title, $location, $inDays, $km, $gain, $loss, $fee, $tag, $status, $xtreme]) {
            $difficulty = $calculator->calculate($km, $gain, $loss);

            $event = Event::updateOrCreate(['slug' => Str::slug($title)], [
                'title' => $title,
                'location' => $location,
                'starts_on' => now()->addDays($inDays)->toDateString(),
                'season_tag' => $tag,
                'summary' => "A guided day hike: {$title}.",
                'description' => "Join us for {$title}. Expect {$km} km with +{$gain} m / -{$loss} m of elevation.",
                'distance_km' => $km,
                'elevation_gain_m' => $gain,
                'elevation_loss_m' => $loss,
                'duration_hours' => round($km / 3, 1),
                'effort_score' => $difficulty->effortScore,
                'difficulty_level' => $difficulty->level,
                'fee_cents' => $fee,
                'capacity' => 30,
                'included' => ['Transportation', 'Reserve entrance', 'Guiding', 'Hiking assistants'],
                'what_to_bring' => ['Hiking shoes', '2L water', 'Lunch & snacks', 'Rain jacket'],
                'itinerary' => ['06:00 First pickup', '09:00 Start of the hike', '12:30 Lunch break with a view', '15:30 End of the hike', 'Drive back to the meeting points'],
                // Demo map only (no GPX file): a small loop near the start area.
                'route_points' => $this->demoRoute($km),
                'is_xtreme' => $xtreme,
                'status' => $status,
                'published_at' => $status === EventStatus::Draft ? null : now(),
                'cancelled_at' => $status === EventStatus::Cancelled ? now() : null,
                'cancellation_reason' => $status === EventStatus::Cancelled ? 'Storm warning in the North.' : null,
            ]);

            $event->meetingPoints()->delete();
            foreach ($pickups as $i => $p) {
                $event->meetingPoints()->create($p + ['position' => $i]);
            }

            // Bookings on the upcoming published hikes: one almost full, the X event awaiting approvals.
            $event->registrations()->delete();
            if ($status === EventStatus::Published && $inDays > 0) {
                EventRegistration::factory()
                    ->count($xtreme ? 3 : 26)
                    ->state($xtreme ? ['status' => RegistrationStatus::Pending] : [])
                    ->sequence(fn ($s) => ['pickup_point' => $pickups[$s->index % 4]['name'], 'pickup_time' => $pickups[$s->index % 4]['pickup_time']])
                    ->for($event)
                    ->create();
            }
        }
    }

    /**
     * A wavy line of roughly $km kilometres in the North Lebanon mountains.
     *
     * @return list<array{float, float}>
     */
    private function demoRoute(float $km): array
    {
        $points = [];
        $steps = 60;

        for ($i = 0; $i <= $steps; $i++) {
            $t = $i / $steps;
            $points[] = [round(34.29 + 0.01 * sin($t * 3 * M_PI), 5), round(35.95 + $t * $km * 0.0085, 5)];
        }

        return $points;
    }
}
