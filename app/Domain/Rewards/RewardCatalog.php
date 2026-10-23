<?php

namespace App\Domain\Rewards;

use App\Domain\Loyalty\Enums\RankTier;

/**
 * The rewards configured in config/rewards.php.
 */
final class RewardCatalog
{
    /**
     * @return list<Reward>
     */
    public function all(): array
    {
        $rewards = [];
        foreach ((array) config('rewards') as $key => $config) {
            $rewards[] = Reward::fromConfig((string) $key, (array) $config);
        }

        return $rewards;
    }

    public function find(string $key): ?Reward
    {
        $config = config("rewards.{$key}");

        return is_array($config) ? Reward::fromConfig($key, $config) : null;
    }

    /**
     * @return list<Reward>
     */
    public function forTier(RankTier $tier): array
    {
        return array_values(array_filter($this->all(), fn (Reward $reward) => $reward->tier === $tier));
    }
}
