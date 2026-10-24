<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Models\User;

/**
 * Team members (About page) in the CMS ("content.manage").
 */
class TeamMemberPolicy
{
    public function manage(User $actor): bool
    {
        return $actor->can(Permission::ManageContent->value);
    }
}
