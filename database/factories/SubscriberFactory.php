<?php

namespace Database\Factories;

use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Subscriber>
 */
class SubscriberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'phone' => null,
            'token' => Str::random(64),
            'confirmed_at' => null,
            'unsubscribed_at' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['confirmed_at' => now()]);
    }

    public function unsubscribed(): static
    {
        return $this->state(fn () => ['unsubscribed_at' => now()]);
    }

    public function phoneOnly(): static
    {
        return $this->state(fn () => ['email' => null, 'phone' => '+9617'.fake()->unique()->numerify('#######')]);
    }
}
