<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Models\User;

/**
 * Covers staff (CMS user) management. Business invariants such as
 * "keep one super admin" live in the Identity actions, not here.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::ManageStaff->value);
    }

    public function create(User $actor): bool
    {
        return $actor->can(Permission::ManageStaff->value);
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->can(Permission::ManageStaff->value) && $user->isStaff();
    }

    public function delete(User $actor, User $user): bool
    {
        return $actor->can(Permission::ManageStaff->value) && $user->isStaff() && ! $actor->is($user);
    }
}
