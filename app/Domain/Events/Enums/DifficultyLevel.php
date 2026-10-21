<?php

namespace App\Domain\Events\Enums;

enum DifficultyLevel: int
{
    case Foundation = 1;
    case Endurance = 2;
    case Advanced = 3;
    case Xtreme = 4;

    public function label(): string
    {
        return "Level {$this->value} · ".match ($this) {
            self::Foundation => 'Foundation',
            self::Endurance => 'Endurance',
            self::Advanced => 'Advanced',
            self::Xtreme => 'Xtreme',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Foundation => 'Easy. Low vertical load; builds base endurance and uphill rhythm.',
            self::Endurance => 'Moderate. Longer distance or steady climbs; needs regular hiking fitness.',
            self::Advanced => 'Challenging. Long day with significant elevation; for experienced hikers.',
            self::Xtreme => 'Very demanding. Long routes with sustained effort; approval required.',
        };
    }

    /**
     * Tailwind classes for the CMS badge.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Foundation => 'bg-emerald-100 text-emerald-800',
            self::Endurance => 'bg-sky-100 text-sky-800',
            self::Advanced => 'bg-amber-100 text-amber-800',
            self::Xtreme => 'bg-red-100 text-red-800',
        };
    }
}
