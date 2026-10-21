<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::ManageEvents->value);
    }

    public function create(User $actor): bool
    {
        return $actor->can(Permission::ManageEvents->value);
    }

    public function update(User $actor, Event $event): bool
    {
        return $actor->can(Permission::ManageEvents->value);
    }

    public function delete(User $actor, Event $event): bool
    {
        return $actor->can(Permission::ManageEvents->value);
    }
}
