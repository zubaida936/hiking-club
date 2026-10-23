<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Models\User;
use App\Models\XApplication;

class XApplicationPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::ReviewApplications->value);
    }

    public function review(User $actor, XApplication $application): bool
    {
        return $actor->can(Permission::ReviewApplications->value);
    }
}
