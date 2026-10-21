<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Exceptions\StaffUserException;
use App\Models\User;

final class DeleteStaffUser
{
    public function handle(User $user, User $actor): void
    {
        if ($user->is($actor)) {
            throw StaffUserException::cannotDeleteSelf();
        }

        if ($user->hasRole(Role::SuperAdmin->value) && User::activeSuperAdminCount() <= 1) {
            throw StaffUserException::lastSuperAdmin();
        }

        $user->delete();
    }
}
