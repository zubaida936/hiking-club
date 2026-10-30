<?php

namespace App\Domain\Rewards\Actions;

use App\Domain\Rewards\Exceptions\RewardException;
use App\Domain\Rewards\HikerRewards;
use App\Domain\Rewards\Reward;
use App\Domain\Rewards\RewardCatalog;
use App\Models\Hiker;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * An organizer hands over a perk (gift card, winter bonus...) and records it, so it can't be given twice.
 */
final class RecordPerkUse
{
    public function __construct(
        private readonly RewardCatalog $catalog,
        private readonly HikerRewards $rewards,
    ) {}

    /**
     * @throws RewardException
     */
    public function handle(Hiker $hiker, string $rewardKey, ?string $note, User $actor): RewardRedemption
    {
        $reward = $this->catalog->find($rewardKey);

        if ($reward === null || $reward->kind !== Reward::PERK) {
            throw RewardException::notHandedOver();
        }

        // The hiker row is locked so two organizers scanning the same code at once can't both hand it over.
        return DB::transaction(function () use ($hiker, $reward, $note, $actor) {
            Hiker::query()->whereKey($hiker->id)->lockForUpdate()->first();

            if (! $this->rewards->isAvailable($hiker, $reward)) {
                throw RewardException::notAvailable();
            }

            return $hiker->rewardRedemptions()->create([
                'reward' => $reward->key,
                'note' => $note,
                'created_by' => $actor->id,
            ]);
        });
    }
}
