<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Models\Hiker;
use App\Models\User;

class HikerPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::ManageLoyalty->value);
    }

    /**
     * Members list and member accounts (invite, password link, deactivate).
     */
    public function manageMembers(User $actor): bool
    {
        return $actor->can(Permission::ManageMembers->value);
    }

    public function adjustPoints(User $actor, Hiker $hiker): bool
    {
        return $actor->can(Permission::ManageLoyalty->value);
    }
}
