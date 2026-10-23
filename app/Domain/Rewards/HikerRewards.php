<?php

namespace App\Domain\Rewards;

use App\Domain\Registrations\Enums\Attendance;
use App\Domain\Registrations\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Hiker;
use App\Models\RewardRedemption;
use Illuminate\Support\Collection;

/**
 * Where a hiker stands with each reward: unlocked or not (and why), uses left, past uses.
 * A reward is unlocked while the hiker's rank is at least the reward's rank and its extra
 * condition is met; uses already made stay recorded even if the rank drops later.
 */
final class HikerRewards
{
    public function __construct(private readonly RewardCatalog $catalog) {}

    /**
     * @return list<array{reward: Reward, unlocked: bool, locked_reason: ?string, uses_left: int, redemptions: Collection<int, RewardRedemption>}>
     */
    public function statesFor(Hiker $hiker): array
    {
        $tier = $hiker->tier();
        $completed = $this->completedHikes($hiker);
        $used = $hiker->rewardRedemptions()->active()->with('registration.event')->latest('id')->get()->groupBy('reward');

        return array_map(function (Reward $reward) use ($tier, $completed, $used) {
            $lockedReason = match (true) {
                ! $tier->isAtLeast($reward->tier) => "Reach {$reward->tier->label()} ({$reward->tier->minPoints()} points)",
                $completed < $reward->minCompletedHikes => "After {$reward->minCompletedHikes} completed hikes ({$completed} so far)",
                default => null,
            };
            /** @var Collection<int, RewardRedemption> $redemptions */
            $redemptions = $used->get($reward->key, collect());

            return [
                'reward' => $reward,
                'unlocked' => $lockedReason === null,
                'locked_reason' => $lockedReason,
                'uses_left' => $reward->isCountable() ? max(0, $reward->uses - $redemptions->count()) : 0,
                'redemptions' => $redemptions,
            ];
        }, $this->catalog->all());
    }

    public function isAvailable(Hiker $hiker, Reward $reward): bool
    {
        foreach ($this->statesFor($hiker) as $state) {
            if ($state['reward']->key === $reward->key) {
                return $state['unlocked'] && $state['uses_left'] > 0;
            }
        }

        return false;
    }

    /**
     * Discount rewards the hiker can use on a booking for this event.
     *
     * @return list<Reward>
     */
    public function discountsFor(Hiker $hiker, Event $event): array
    {
        return array_values(array_map(
            fn (array $state) => $state['reward'],
            array_filter($this->statesFor($hiker), fn (array $state) => $state['unlocked']
                && $state['uses_left'] > 0
                && $state['reward']->isDiscountFor($event)),
        ));
    }

    /**
     * Whether an unlocked reward of this kind applies (e.g. Elite's Xtreme eligibility).
     */
    public function has(Hiker $hiker, string $kind): bool
    {
        foreach ($this->statesFor($hiker) as $state) {
            if ($state['unlocked'] && $state['reward']->kind === $kind) {
                return true;
            }
        }

        return false;
    }

    private function completedHikes(Hiker $hiker): int
    {
        return $hiker->registrations()
            ->where('status', RegistrationStatus::Confirmed)
            ->where('attendance', Attendance::Attended)
            ->count();
    }
}
