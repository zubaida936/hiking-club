<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Models\User;

/**
 * Privacy Policy and Terms in the CMS ("content.manage").
 */
class LegalPagePolicy
{
    public function manage(User $actor): bool
    {
        return $actor->can(Permission::ManageContent->value);
    }
}
