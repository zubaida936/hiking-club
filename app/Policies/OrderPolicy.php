<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Models\User;

/**
 * Shop management in the CMS ("shop.manage").
 */
class OrderPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::ManageShop->value);
    }

    public function manage(User $actor): bool
    {
        return $actor->can(Permission::ManageShop->value);
    }
}
