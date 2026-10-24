<?php

namespace App\Domain\Rewards;

use App\Domain\Loyalty\Enums\RankTier;
use App\Models\Event;

/**
 * One reward from config/rewards.php.
 */
final readonly class Reward
{
    public const DISCOUNT = 'discount';

    public const BONUS_POINTS = 'bonus_points';

    public const PERK = 'perk';

    public const XTREME = 'xtreme';

    public const INFO = 'info';

    /**
     * @param  list<int>  $levels  difficulty levels a discount applies to (empty = any)
     */
    public function __construct(
        public string $key,
        public RankTier $tier,
        public string $label,
        public string $kind,
        public int $uses,
        public int $percent = 0,
        public int $points = 0,
        public array $levels = [],
        public int $minCompletedHikes = 0,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(string $key, array $config): self
    {
        $kind = (string) $config['kind'];

        return new self(
            key: $key,
            tier: RankTier::from((string) $config['tier']),
            label: (string) $config['label'],
            kind: $kind,
            // Discounts and perks are one-time unless configured; bonus points are granted once.
            uses: (int) ($config['uses'] ?? (in_array($kind, [self::DISCOUNT, self::PERK, self::BONUS_POINTS], true) ? 1 : 0)),
            percent: (int) ($config['percent'] ?? 0),
            points: (int) ($config['points'] ?? 0),
            levels: array_map('intval', (array) ($config['levels'] ?? [])),
            minCompletedHikes: (int) ($config['min_completed_hikes'] ?? 0),
        );
    }

    /**
     * How a member gets the reward, for the public Rank System page.
     */
    public function howItWorks(): string
    {
        $note = match ($this->kind) {
            self::DISCOUNT => 'Choose it on the booking form when you book'.($this->uses > 1 ? " ({$this->uses} uses)" : '').'.',
            self::BONUS_POINTS => 'Added to your points automatically when you reach the rank.',
            self::PERK => 'Handed over by the organizers.',
            self::XTREME => 'Your X event bookings are confirmed straight away.',
            default => 'Coming soon.',
        };

        return $this->minCompletedHikes > 0 ? "After {$this->minCompletedHikes} completed hikes. {$note}" : $note;
    }

    /**
     * Rewards with a limited number of uses that are tracked.
     */
    public function isCountable(): bool
    {
        return $this->uses > 0;
    }

    public function isDiscountFor(Event $event): bool
    {
        return $this->kind === self::DISCOUNT
            && $event->fee_cents > 0
            && ($this->levels === [] || in_array($event->difficulty_level->value, $this->levels, true));
    }

    public function discountCents(int $feeCents): int
    {
        return (int) min($feeCents, round($feeCents * $this->percent / 100));
    }
}
