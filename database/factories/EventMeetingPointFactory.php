<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventMeetingPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventMeetingPoint>
 */
class EventMeetingPointFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->randomElement(['Sour', 'Saida', 'Beirut - Jnah', 'Beirut - Downtown', 'Jounieh']),
            'pickup_time' => fake()->randomElement(['05:50', '06:40', '07:30', '07:40']),
            'position' => 0,
        ];
    }
}
