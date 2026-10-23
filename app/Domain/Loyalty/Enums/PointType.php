<?php

namespace App\Domain\Loyalty\Enums;

enum PointType: string
{
    case Hike = 'hike';
    case EarlyBooking = 'early_booking';
    case Referral = 'referral';
    // Manual correction by an organizer.
    case Adjustment = 'adjustment';
    // One-time bonus that comes with a rank (config/rewards.php, kind bonus_points).
    case RankBonus = 'rank_bonus';

    public function label(): string
    {
        return match ($this) {
            self::Hike => 'Completed hike',
            self::EarlyBooking => 'Early booking',
            self::Referral => 'New hiker brought',
            self::Adjustment => 'Adjustment',
            self::RankBonus => 'Rank bonus',
        };
    }

    /**
     * Types the engine derives from bookings (everything except manual adjustments).
     *
     * @return list<self>
     */
    public static function automatic(): array
    {
        return [self::Hike, self::EarlyBooking, self::Referral];
    }
}
