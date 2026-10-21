<?php

use App\Domain\Identity\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class))
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

/**
 * Create a user with the given role (roles are seeded in beforeEach).
 */
function userWithRole(Role $role, array $attributes = []): User
{
    return User::factory()->withRole($role)->create($attributes);
}
