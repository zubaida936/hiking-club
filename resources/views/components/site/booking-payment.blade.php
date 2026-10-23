@props(['registration'])

@php
    use App\Domain\Payments\Enums\PaymentMethod;

    $event = $registration->event;
    $method = $registration->payment_method;
@endphp

{{-- What the hiker still has to do to pay for this booking. --}}
@if ($event->fee_cents > 0 && ! $registration->isCancelled())
    <section aria-labelledby="payment-heading" class="mt-8 rounded-2xl border border-slate-200 p-6">
        <h2 id="payment-heading" class="font-display text-xl font-bold uppercase tracking-wide text-brand-900">Payment · {{ $event->formattedFee() }}</h2>

        @if ($registration->paid_at)
            <p class="mt-3 font-semibold text-brand-700">✓ Paid{{ $method ? ' ('.$method->label().')' : '' }}. Thank you!</p>
        @elseif (! $registration->isConfirmed())
            <p class="mt-3 text-slate-600">You'll pay once your seat is confirmed. We'll let you know.</p>
        @elseif ($method === PaymentMethod::Online && PaymentMethod::Online->isAvailable())
            <p class="mt-3 text-slate-600">Pay securely online by card or wallet.</p>
            <form method="POST" action="{{ route('registrations.pay', $registration) }}" class="mt-4">
                @csrf
                <button class="rounded-full bg-accent-500 px-8 py-3 font-display font-bold uppercase tracking-wide text-white hover:bg-accent-600">Pay {{ $event->formattedFee() }} now</button>
            </form>
        @elseif ($method?->isWalletTransfer())
            <ol class="mt-3 list-decimal space-y-1 pl-5 text-slate-700">
                <li>Open <strong>{{ $method === PaymentMethod::Whish ? 'Whish Money' : 'OMT Pay' }}</strong> and send <strong>{{ $event->formattedFee() }}</strong> to
                    <strong>{{ $method->accountName() }}</strong>, <strong class="whitespace-nowrap">{{ $method->accountNumber() }}</strong>.</li>
                <li>Write your booking reference <strong>{{ $registration->reference }}</strong> in the transfer note.</li>
                <li>Enter the transaction number below so we can confirm your payment faster.</li>
            </ol>
            @if ($registration->payment_reference)
                <p class="mt-4 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-900">We received transaction number <strong>{{ $registration->payment_reference }}</strong>. We'll confirm your payment soon.</p>
            @endif
            <form method="POST" action="{{ route('registrations.transfer', $registration) }}" class="mt-4 flex flex-wrap items-end gap-3">
                @csrf
                <div class="min-w-0 flex-1">
                    <x-site.field name="payment_reference" label="Transaction number" maxlength="60" placeholder="e.g. 1234567890" />
                </div>
                <button class="rounded-full border border-brand-600 px-6 py-3 font-display font-semibold uppercase tracking-wide text-brand-700 hover:bg-brand-50">
                    {{ $registration->payment_reference ? 'Update' : 'Send' }}
                </button>
            </form>
        @elseif ($method === PaymentMethod::Cash)
            <p class="mt-3 text-slate-600">Pay <strong>{{ $event->formattedFee() }}</strong> in cash to the organizers at the pickup point.</p>
        @else
            <p class="mt-3 text-slate-600">{{ config('site.booking.payment_note') }}</p>
        @endif
    </section>
@endif
