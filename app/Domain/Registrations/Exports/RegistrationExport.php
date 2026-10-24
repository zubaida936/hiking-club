<?php

namespace App\Domain\Registrations\Exports;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Support\Csv;
use Illuminate\Support\Collection;

/**
 * An event's registrations as a spreadsheet (CSV) or as a text list to paste
 * into the guides' WhatsApp group.
 */
final class RegistrationExport
{
    /**
     * @param  Collection<int, EventRegistration>  $registrations
     */
    public function __construct(
        private readonly Event $event,
        private readonly Collection $registrations,
    ) {}

    /**
     * @return list<list<string>> header row first
     */
    public function rows(): array
    {
        $timezone = config('site.timezone');

        $rows = [[
            'Reference', 'Status', 'Name', 'Phone', 'Email', 'Pickup point', 'Pickup time',
            'Emergency contact', 'Emergency phone', 'Notes', 'Paid', 'Payment method', 'Transaction number', 'Attendance', 'Booked at',
        ]];

        foreach ($this->registrations as $r) {
            $rows[] = [
                $r->reference,
                $r->status->label(),
                $r->name,
                $r->phone,
                (string) $r->email,
                (string) $r->pickup_point,
                (string) $r->pickupTimeShort(),
                $r->emergency_contact_name,
                $r->emergency_contact_phone,
                (string) $r->notes,
                $r->paid_at ? 'Yes' : 'No',
                (string) $r->payment_method?->label(),
                (string) $r->payment_reference,
                (string) $r->attendance?->label(),
                $r->created_at->setTimezone($timezone)->format('Y-m-d H:i'),
            ];
        }

        return $rows;
    }

    /**
     * @param  resource  $handle
     */
    public function writeCsv($handle): void
    {
        Csv::write($handle, $this->rows());
    }

    public function filename(): string
    {
        return $this->event->starts_on->format('Y-m-d').'-'.$this->event->slug.'-registrations.csv';
    }

    /**
     * Hikers holding a seat, grouped by pickup point in pickup order:
     *
     *   *Horch Ehden Nature Reserve*
     *   Sat 7 Nov 2026
     *
     *   📍 *Sour* 05:50
     *   1. Rima Haddad +96170123456
     */
    public function whatsappText(): string
    {
        $lines = ["*{$this->event->title}*", $this->event->starts_on->format('D j M Y')];

        $groups = $this->registrations
            ->filter(fn (EventRegistration $r) => $r->holdsSeat())
            ->sortBy(fn (EventRegistration $r) => [$r->pickup_time ?? '99', $r->pickup_point ?? '', mb_strtolower($r->name)])
            ->groupBy(fn (EventRegistration $r) => $r->pickup_point ? "📍 *{$r->pickup_point}* {$r->pickupTimeShort()}" : '📍 *No pickup point*');

        $total = 0;
        foreach ($groups as $heading => $group) {
            $lines[] = '';
            $lines[] = (string) $heading;
            foreach ($group->values() as $i => $r) {
                $lines[] = ($i + 1).". {$r->name} {$r->phone}".($r->isPending() ? ' (awaiting approval)' : '');
                $total++;
            }
        }

        $lines[] = '';
        $lines[] = "Total: {$total}";

        return implode("\n", $lines);
    }
}
