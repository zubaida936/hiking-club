<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Data\StaffUserData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateStaffUser
{
    public function handle(StaffUserData $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'password' => $data->password,
                'is_active' => $data->isActive,
                'email_verified_at' => now(),
            ]);

            $user->syncRoles([$data->role->value]);

            return $user;
        });
    }
}
