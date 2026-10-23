<?php

namespace App\Domain\Registrations\Enums;

enum Attendance: string
{
    case Attended = 'attended';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Attended => 'Attended',
            self::NoShow => 'No-show',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Attended => 'bg-emerald-100 text-emerald-800',
            self::NoShow => 'bg-red-100 text-red-700',
        };
    }
}
