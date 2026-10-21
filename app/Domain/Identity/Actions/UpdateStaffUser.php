<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Data\StaffUserData;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Exceptions\StaffUserException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateStaffUser
{
    public function handle(User $user, StaffUserData $data, User $actor): User
    {
        $demotesSuperAdmin = $user->hasRole(Role::SuperAdmin->value)
            && ($data->role !== Role::SuperAdmin || ! $data->isActive);

        if ($demotesSuperAdmin && User::activeSuperAdminCount() <= 1) {
            throw StaffUserException::lastSuperAdmin();
        }

        if ($user->is($actor) && ! $data->isActive) {
            throw StaffUserException::cannotDeactivateSelf();
        }

        return DB::transaction(function () use ($user, $data) {
            $user->fill([
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'is_active' => $data->isActive,
            ]);

            if ($data->password !== null && $data->password !== '') {
                $user->password = $data->password;
            }

            $user->save();
            $user->syncRoles([$data->role->value]);

            return $user;
        });
    }
}
