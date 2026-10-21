<?php

namespace Database\Seeders;

use App\Domain\Identity\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = config('cms.seed_admin');

        $user = User::firstOrCreate(['email' => $admin['email']], [
            'name' => $admin['name'],
            'password' => $admin['password'],
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([Role::SuperAdmin->value]);
    }
}
