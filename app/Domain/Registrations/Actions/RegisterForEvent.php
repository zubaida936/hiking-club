<?php

namespace App\Domain\Registrations\Actions;

use App\Domain\Members\Actions\ResolveHiker;
use App\Domain\Registrations\Data\RegistrationData;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Domain\Registrations\Exceptions\RegistrationException;
use App\Domain\Rewards\Exceptions\RewardException;
use App\Domain\Rewards\HikerRewards;
use App\Domain\Rewards\Reward;
use App\Domain\Rewards\RewardCatalog;
use App\Domain\XEvents\XAccess;
use App\Mail\RegistrationConfirmationMail;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Hiker;
use App\Support\ReferenceCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Books a seat on an event. The event row is locked while seats are counted,
 * so two people can never take the last seat. X events are only for logged-in
 * hikers approved for X Events (or Elite members). When the event is full
 * and has a waitlist, the visitor joins the waitlist instead.
 */
final class RegisterForEvent
{
    public function __construct(
        private readonly ResolveHiker $resolveHiker,
        private readonly RewardCatalog $catalog,
        private readonly HikerRewards $rewards,
        private readonly XAccess $xAccess,
    ) {}

    public function handle(Event $event, RegistrationData $data): EventRegistration
    {
        $registration = DB::transaction(function () use ($event, $data) {
            /** @var Event $event */
            $event = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();

            if (! $event->isOpenForRegistration()) {
                throw RegistrationException::closed();
            }
            if ($event->requiresLevelAcknowledgement() && ! $data->levelAcknowledged) {
                throw RegistrationException::levelNotAcknowledged();
            }
            if ($event->registrations()->where('status', '!=', RegistrationStatus::Cancelled)->where('phone', $data->phone)->exists()) {
                throw RegistrationException::alreadyRegistered();
            }

            $pickup = $data->meetingPointId !== null ? $event->meetingPoints()->find($data->meetingPointId) : null;
            $hiker = $this->resolveHiker->handle($data->phone, $data->name, $data->email);
            // Unknown numbers are ignored: only existing hikers can earn the referral bonus.
            $referrerId = $data->referrerPhone !== null ? Hiker::where('phone', $data->referrerPhone)->value('id') : null;
            // Rank perks and rewards only for the logged-in member's own booking.
            $ownBooking = $data->memberHikerId !== null && $data->memberHikerId === $hiker->id;
            // X events: only approved hikers (or Elite members), booking for themselves while logged in.
            if ($event->is_xtreme && ! ($ownBooking && $this->xAccess->allows($hiker))) {
                throw RegistrationException::xApprovalRequired();
            }
            $reward = $this->chooseReward($data, $event, $hiker, $ownBooking);

            $status = match (true) {
                ! $event->isSoldOut() => RegistrationStatus::Confirmed,
                $event->waitlist_enabled => RegistrationStatus::Waitlisted,
                default => throw RegistrationException::soldOut(),
            };

            $discount = $reward?->discountCents($event->fee_cents) ?? 0;

            $registration = $event->registrations()->create([
                'hiker_id' => $hiker->id,
                'referred_by_hiker_id' => $referrerId !== $hiker->id ? $referrerId : null,
                'reference' => ReferenceCode::unique(EventRegistration::class),
                'token' => Str::random(64),
                'status' => $status,
                'name' => $data->name,
                'phone' => $data->phone,
                'email' => $data->email,
                'pickup_point' => $pickup?->name,
                'pickup_time' => $pickup?->pickup_time,
                'emergency_contact_name' => $data->emergencyContactName,
                'emergency_contact_phone' => $data->emergencyContactPhone,
                'notes' => $data->notes,
                'amount_due_cents' => $reward ? $event->fee_cents - $discount : null,
                'payment_method' => $event->fee_cents - $discount > 0 ? $data->paymentMethod : null,
                'terms_accepted_at' => now(),
                'level_acknowledged_at' => $event->requiresLevelAcknowledgement() ? now() : null,
            ]);

            if ($reward) {
                $hiker->rewardRedemptions()->create([
                    'reward' => $reward->key,
                    'event_registration_id' => $registration->id,
                    'discount_cents' => $discount,
                ]);
            }

            return $registration;
        });

        if ($registration->email !== null) {
            Mail::to($registration->email)->queue(new RegistrationConfirmationMail($registration));
        }

        return $registration;
    }

    /**
     * @throws RewardException
     */
    private function chooseReward(RegistrationData $data, Event $event, Hiker $hiker, bool $ownBooking): ?Reward
    {
        if ($data->rewardKey === null) {
            return null;
        }
        if (! $ownBooking) {
            throw RewardException::onlyOwnBooking();
        }

        $reward = $this->catalog->find($data->rewardKey);
        if ($reward === null || ! $reward->isDiscountFor($event)) {
            throw RewardException::notForThisHike();
        }

        // Lock the hiker so two bookings at once can't both use the last use of a reward.
        Hiker::whereKey($hiker->id)->lockForUpdate()->first();
        if (! $this->rewards->isAvailable($hiker, $reward)) {
            throw RewardException::notAvailable();
        }

        return $reward;
    }
}
