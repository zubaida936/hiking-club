<?php

namespace App\Domain\Events\Enums;

/**
 * Editorial status. Whether an event is upcoming or past is derived from
 * its date, not stored.
 */
enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-200 text-slate-700',
            self::Published => 'bg-emerald-100 text-emerald-800',
            self::Cancelled => 'bg-red-100 text-red-700',
        };
    }
}
