<?php

namespace App\Domain\Members\Actions;

use App\Domain\Members\Exceptions\MemberException;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Domain\Shop\Enums\OrderStatus;
use App\Models\EventRegistration;
use App\Models\Hiker;
use App\Models\Order;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * A member deletes their own account (required by the App Store and Google Play; also on the website).
 *
 * Decided 2026-10-29: anonymise. The login, tokens, sessions, points, rewards and X applications are deleted;
 * bookings and orders stay for the club's records (payments, attendance, stats) with every personal detail
 * replaced. Refused while the member still has upcoming bookings or open orders, so organizers never lose the
 * contact of someone who is still coming (or paid).
 */
final class DeleteAccount
{
    public const NAME = 'Deleted member';

    /** Written in place of phone numbers (the columns can't be empty). */
    public const PHONE = 'deleted';

    /**
     * @throws MemberException
     */
    public function handle(User $user, string $password): void
    {
        if ($user->isStaff()) {
            throw MemberException::staffCannotDeleteSelf();
        }
        if (! Hash::check($password, $user->password)) {
            throw MemberException::wrongPassword();
        }

        $hiker = $user->hiker;
        if ($hiker !== null && $this->hasOpenBookings($hiker)) {
            throw MemberException::hasOpenBookings();
        }

        DB::transaction(function () use ($user, $hiker) {
            if ($hiker !== null) {
                $this->anonymise($hiker);
            }

            // Their newsletter sign-ups too. Only exact matches: never a query without a condition.
            $emails = array_values(array_filter([$user->email, $hiker?->email]));
            $phones = array_values(array_filter([$user->phone, $hiker?->phone]));
            if ($emails !== []) {
                Subscriber::query()->whereIn('email', $emails)->delete();
            }
            if ($phones !== []) {
                Subscriber::query()->whereIn('phone', $phones)->delete();
            }

            $user->tokens()->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->syncRoles([]);
            // Without a remember token, a logout after this has nothing to save: Auth::logout() would otherwise
            // write a new one and save() the deleted user back into the table.
            $user->setRememberToken('');
            // Reset codes go with the user (cascade); forceDelete: a soft-deleted row would keep the phone and email.
            $user->forceDelete();
        });
    }

    private function hasOpenBookings(Hiker $hiker): bool
    {
        $upcoming = $hiker->registrations()
            ->where('status', '!=', RegistrationStatus::Cancelled)
            ->whereHas('event', fn ($q) => $q->upcoming())
            ->exists();

        $openOrders = Order::query()
            ->where('hiker_id', $hiker->id)
            ->whereNotIn('status', [OrderStatus::Completed, OrderStatus::Cancelled])
            ->exists();

        return $upcoming || $openOrders;
    }

    /**
     * Personal details out of every booking and order; then the hiker row goes, which also deletes its
     * points, rewards used and X applications (database cascade) and unlinks the bookings and orders.
     */
    private function anonymise(Hiker $hiker): void
    {
        EventRegistration::query()->where('hiker_id', $hiker->id)->update([
            'name' => self::NAME,
            'phone' => self::PHONE,
            'email' => null,
            'emergency_contact_name' => self::NAME,
            'emergency_contact_phone' => self::PHONE,
            'notes' => null,
        ]);

        Order::query()->where('hiker_id', $hiker->id)->update([
            'name' => self::NAME,
            'phone' => self::PHONE,
            'email' => null,
            'delivery_city' => null,
            'delivery_address' => null,
            'notes' => null,
        ]);

        $hiker->delete();
    }
}
