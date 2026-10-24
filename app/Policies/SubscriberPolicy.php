<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Models\User;

/**
 * Newsletter subscriber list in the CMS ("subscribers.manage").
 */
class SubscriberPolicy
{
    public function manage(User $actor): bool
    {
        return $actor->can(Permission::ManageSubscribers->value);
    }
}
