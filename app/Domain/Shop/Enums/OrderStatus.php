<?php

namespace App\Domain\Shop\Enums;

enum OrderStatus: string
{
    case New = 'new';
    case Preparing = 'preparing';
    // Ready for pickup, or handed to the driver.
    case Ready = 'ready';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(?Fulfilment $fulfilment = null): string
    {
        return match ($this) {
            self::New => 'Received',
            self::Preparing => 'Being prepared',
            self::Ready => $fulfilment === Fulfilment::Delivery ? 'Out for delivery' : 'Ready for pickup',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::New => 'bg-amber-100 text-amber-800',
            self::Preparing => 'bg-sky-100 text-sky-800',
            self::Ready => 'bg-violet-100 text-violet-800',
            self::Completed => 'bg-emerald-100 text-emerald-800',
            self::Cancelled => 'bg-slate-200 text-slate-700',
        };
    }

    /**
     * Statuses an organizer can move an order to from this one.
     *
     * @return list<self>
     */
    public function next(): array
    {
        return match ($this) {
            self::New => [self::Preparing, self::Ready, self::Completed],
            self::Preparing => [self::Ready, self::Completed],
            self::Ready => [self::Completed],
            self::Completed, self::Cancelled => [],
        };
    }
}
