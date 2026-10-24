<?php

namespace Database\Factories;

use App\Models\Album;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Album>
 */
class AlbumFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => fn (array $attributes) => Str::slug($attributes['title']),
            'event_id' => null,
            'description' => null,
            'taken_on' => now()->subWeeks(2)->toDateString(),
            'is_published' => true,
        ];
    }
}
