<?php

namespace Database\Factories;

use App\Domain\XEvents\Enums\XApplicationStatus;
use App\Models\Hiker;
use App\Models\XApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<XApplication>
 */
class XApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'hiker_id' => Hiker::factory(),
            'status' => XApplicationStatus::Pending,
            'name' => fake()->name(),
            'phone' => '+9617'.fake()->unique()->numerify('#######'),
            'age' => fake()->numberBetween(20, 55),
            'city' => fake()->randomElement(['Beirut', 'Jounieh', 'Zahle', 'Saida', 'Tripoli']),
            'hike_frequency' => 'twice_month',
            'hardest_route' => 'Qornet es Sawda summit',
            'hardest_km' => 22,
            'hardest_elevation_m' => 1300,
            'hardest_duration_hours' => 9,
            'longest_km' => 28,
            'max_ascent_m' => 1500,
            'pace' => 'steady',
            'steady_pace' => true,
            'main_challenge' => 'Long descents at the end of the day.',
            'experience' => ['winter', 'night'],
            'gear' => ['boots', 'backpack', 'water', 'headlamp'],
            'consent_at' => now(),
        ];
    }
}
