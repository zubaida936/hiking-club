<?php

namespace App\Domain\Registrations\Enums;

enum RegistrationStatus: string
{
    case Confirmed = 'confirmed';
    // X events: the seat is held until an organizer approves the hiker.
    case Pending = 'pending';
    case Cancelled = 'cancelled';

    /**
     * Statuses that take a seat.
     *
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Confirmed, self::Pending];
    }

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmed',
            self::Pending => 'Awaiting approval',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Confirmed => 'bg-emerald-100 text-emerald-800',
            self::Pending => 'bg-amber-100 text-amber-800',
            self::Cancelled => 'bg-slate-200 text-slate-700',
        };
    }
}
