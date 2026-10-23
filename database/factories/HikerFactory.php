<?php

namespace Database\Factories;

use App\Models\Hiker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hiker>
 */
class HikerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'phone' => '+9617'.fake()->unique()->numerify('#######'),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
        ];
    }
}
