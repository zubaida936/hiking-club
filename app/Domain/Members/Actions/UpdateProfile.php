<?php

namespace App\Domain\Members\Actions;

use App\Domain\Members\Data\ProfileData;
use App\Models\Hiker;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * A member updates their name, email and emergency contact (app and website). The account and the hiker
 * profile hold the same name and email, so both change together. Asking for the password before an email
 * change (where reset codes go) is the request's job.
 */
final class UpdateProfile
{
    public function handle(User $user, ProfileData $data): User
    {
        DB::transaction(function () use ($user, $data) {
            $user->forceFill(['name' => $data->name, 'email' => $data->email])->save();

            /** @var ?Hiker $hiker */
            $hiker = $user->hiker;
            $hiker?->update([
                'name' => $data->name,
                'email' => $data->email,
                'emergency_contact_name' => $data->emergencyContactName,
                'emergency_contact_phone' => $data->emergencyContactPhone,
            ]);
        });

        return $user;
    }
}
