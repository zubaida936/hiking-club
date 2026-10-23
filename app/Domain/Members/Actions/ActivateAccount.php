<?php

namespace App\Domain\Members\Actions;

use App\Domain\Identity\Enums\Role;
use App\Domain\Members\Exceptions\MemberException;
use App\Models\Hiker;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * No open signup: a hiker who booked a hike proves the phone number is theirs
 * with a booking reference (only they received it), then chooses a password.
 */
final class ActivateAccount
{
    /**
     * @throws MemberException
     */
    public function verify(string $phone, string $reference): Hiker
    {
        $hiker = Hiker::where('phone', $phone)
            ->whereHas('registrations', fn ($q) => $q->where('reference', strtoupper(trim($reference))))
            ->first();

        if ($hiker === null) {
            throw MemberException::cannotActivate();
        }
        if ($hiker->user_id !== null || User::withTrashed()->where('phone', $phone)->exists()) {
            throw MemberException::alreadyActive();
        }

        return $hiker;
    }

    /**
     * @throws MemberException
     */
    public function handle(Hiker $hiker, string $password): User
    {
        return DB::transaction(function () use ($hiker, $password) {
            /** @var Hiker $hiker */
            $hiker = Hiker::whereKey($hiker->id)->lockForUpdate()->firstOrFail();

            if ($hiker->user_id !== null || User::withTrashed()->where('phone', $hiker->phone)->exists()) {
                throw MemberException::alreadyActive();
            }

            $emailFree = $hiker->email !== null && ! User::withTrashed()->where('email', $hiker->email)->exists();

            $user = User::create([
                'name' => $hiker->name,
                'phone' => $hiker->phone,
                'email' => $emailFree ? $hiker->email : null,
                'password' => $password,
            ]);
            $user->assignRole(Role::Member->value);

            $hiker->update(['user_id' => $user->id]);

            return $user;
        });
    }
}
