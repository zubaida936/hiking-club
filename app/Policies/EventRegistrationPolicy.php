<?php

namespace App\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Models\EventRegistration;
use App\Models\User;

class EventRegistrationPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::ViewRegistrations->value);
    }

    /**
     * Edit details, approve, cancel and mark paid.
     */
    public function update(User $actor, EventRegistration $registration): bool
    {
        return $actor->can(Permission::ManageRegistrations->value);
    }

    /**
     * A member managing their own booking from the dashboard.
     */
    public function cancelOwn(User $actor, EventRegistration $registration): bool
    {
        return $registration->hiker_id !== null && $actor->hiker?->id === $registration->hiker_id;
    }

    public function markAttendance(User $actor, EventRegistration $registration): bool
    {
        return $actor->can(Permission::ManageAttendance->value);
    }
}
