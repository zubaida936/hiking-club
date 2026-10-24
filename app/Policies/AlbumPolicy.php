<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Models\User;

/**
 * Gallery albums and photos in the CMS ("media.manage").
 */
class AlbumPolicy
{
    public function manage(User $actor): bool
    {
        return $actor->can(Permission::ManageMedia->value);
    }
}
