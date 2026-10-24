<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Models\User;

/**
 * Guide articles in the CMS ("content.manage").
 */
class ArticlePolicy
{
    public function manage(User $actor): bool
    {
        return $actor->can(Permission::ManageContent->value);
    }
}
