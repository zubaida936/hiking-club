<?php

namespace Database\Factories;

use App\Domain\Events\Difficulty\DifficultyCalculator;
use App\Domain\Events\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventMeetingPoint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->randomElement([
            'Horch Ehden Nature Reserve', 'Qadisha Valley Descent', 'Tannourine Cedars Loop',
            'Jabal Moussa Biosphere', 'Chouf Cedars Ridge', 'Baskinta Literary Trail',
            'Qornet es Sawda Summit', 'Wadi Qannoubine', 'Akoura Hidden Trail', 'Bisri Valley Walk',
        ]).' '.fake()->unique()->numberBetween(1, 9999);

        $distance = fake()->randomFloat(1, 6, 28);
        $gain = fake()->numberBetween(100, 1400);
        $loss = fake()->numberBetween(100, 1400);
        $difficulty = app(DifficultyCalculator::class)->calculate($distance, $gain, $loss);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'season_tag' => fake()->randomElement([null, '🍂 Autumn Trail', '❄️ Snow Hike', '🌸 Spring Trail']),
            'location' => fake()->randomElement(['Ehden', 'Tannourine', 'Jezzine', 'Baskinta', 'Bcharre']),
            'starts_on' => now()->addDays(fake()->numberBetween(3, 60))->toDateString(),
            'summary' => fake()->sentence(14),
            'description' => fake()->paragraphs(3, true),
            'distance_km' => $distance,
            'elevation_gain_m' => $gain,
            'elevation_loss_m' => $loss,
            'duration_hours' => fake()->randomFloat(1, 3, 10),
            'effort_score' => $difficulty->effortScore,
            'difficulty_level' => $difficulty->level,
            'fee_cents' => fake()->randomElement([2500, 3000, 3500, 4000]),
            'capacity' => fake()->randomElement([20, 30, 40]),
            'included' => ['Transportation', 'Reserve entrance', 'Guiding', 'Hiking assistants'],
            'what_to_bring' => ['Hiking shoes', '2L water', 'Snacks', 'Rain jacket'],
            'is_xtreme' => false,
            'status' => EventStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => EventStatus::Published, 'published_at' => now()]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => EventStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => 'Bad weather forecast',
        ]);
    }

    public function past(): static
    {
        return $this->state(fn () => ['starts_on' => now()->subDays(fake()->numberBetween(7, 120))->toDateString()]);
    }

    public function withMeetingPoints(int $count = 2): static
    {
        return $this->has(
            EventMeetingPoint::factory()->count($count)->sequence(fn ($s) => ['position' => $s->index]),
            'meetingPoints',
        );
    }
}
