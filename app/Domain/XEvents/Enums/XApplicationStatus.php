<?php

namespace App\Domain\XEvents\Enums;

enum XApplicationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    // Approved before, access taken away later (reassessment).
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Under review',
            self::Approved => 'Approved',
            self::Rejected => 'Not approved',
            self::Revoked => 'Access revoked',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800',
            self::Approved => 'bg-emerald-100 text-emerald-800',
            self::Rejected => 'bg-slate-200 text-slate-700',
            self::Revoked => 'bg-red-100 text-red-700',
        };
    }
}
