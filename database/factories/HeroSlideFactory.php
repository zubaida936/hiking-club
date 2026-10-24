<?php

namespace Database\Factories;

use App\Models\HeroSlide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HeroSlide>
 */
class HeroSlideFactory extends Factory
{
    public function definition(): array
    {
        return [
            'eyebrow' => null,
            'heading' => fake()->unique()->sentence(4),
            'text' => fake()->sentence(),
            'cta_label' => 'See hikes',
            'cta_url' => '/events',
            'image_path' => null,
            'position' => 0,
            'is_active' => true,
        ];
    }
}
