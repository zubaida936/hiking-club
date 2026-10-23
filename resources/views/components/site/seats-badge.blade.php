@props(['event'])

{{-- "Sold out" / "Only N seats left" for an upcoming event that is taking bookings. --}}
@if ($event->isOpenForRegistration())
    @if ($event->isSoldOut())
        <span {{ $attributes->class('rounded-full bg-slate-900/85 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white') }}>Sold out</span>
    @elseif ($event->seatsLeft() <= 5)
        <span {{ $attributes->class('rounded-full bg-accent-500 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white') }}>Only {{ $event->seatsLeft() }} {{ Str::plural('seat', $event->seatsLeft()) }} left</span>
    @endif
@endif
