<?php

namespace Database\Factories;

use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamMember>
 */
class TeamMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'role' => 'Guide',
            'bio' => null,
            'photo_path' => null,
            'position' => 0,
            'is_active' => true,
        ];
    }
}
