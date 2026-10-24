<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Models\User;

/**
 * Homepage hero slides in the CMS ("content.manage").
 */
class HeroSlidePolicy
{
    public function manage(User $actor): bool
    {
        return $actor->can(Permission::ManageContent->value);
    }
}
