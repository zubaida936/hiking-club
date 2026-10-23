<?php

namespace App\Domain\Loyalty\Enums;

/**
 * A hiker's rank, from their total points (thresholds in config/points.php → tiers).
 */
enum RankTier: string
{
    case Pathfinder = 'pathfinder';
    case Trekker = 'trekker';
    case Mountaineer = 'mountaineer';
    case Highlander = 'highlander';
    case Elite = 'elite';

    public static function forPoints(int $points): self
    {
        $tier = self::Pathfinder;

        foreach (self::cases() as $case) {
            if ($points >= $case->minPoints()) {
                $tier = $case;
            }
        }

        return $tier;
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function minPoints(): int
    {
        return (int) config("points.tiers.{$this->value}");
    }

    public function next(): ?self
    {
        $cases = self::cases();
        $index = array_search($this, $cases, true);

        return $cases[$index + 1] ?? null;
    }

    /**
     * @return list<string>
     */
    public function rewards(): array
    {
        return array_values((array) config("points.tier_rewards.{$this->value}", []));
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pathfinder => 'bg-slate-100 text-slate-700',
            self::Trekker => 'bg-emerald-100 text-emerald-800',
            self::Mountaineer => 'bg-sky-100 text-sky-800',
            self::Highlander => 'bg-violet-100 text-violet-800',
            self::Elite => 'bg-amber-100 text-amber-900',
        };
    }
}
