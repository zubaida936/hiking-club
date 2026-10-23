@php
    use App\Domain\Payments\Enums\PaymentMethod;
    use App\Domain\Shop\Enums\Fulfilment;
    use App\Models\Product;

    $method = $order->payment_method;
@endphp

<x-site.layout :title="'Order '.$order->reference" :noindex="true">
    <section class="bg-brand-800 px-4 py-14 text-center text-white sm:px-6">
        <p class="font-display text-sm font-semibold uppercase tracking-[0.2em] text-accent-400">Order reference</p>
        <p class="mt-2 font-display text-4xl font-bold tracking-[0.15em]">{{ $order->reference }}</p>
        <h1 class="mt-6 font-display text-3xl font-bold uppercase tracking-wide sm:text-4xl">
            {{ $order->status === \App\Domain\Shop\Enums\OrderStatus::New ? 'Thanks for your order!' : $order->statusLabel() }}
        </h1>
        <p class="mx-auto mt-3 max-w-xl text-white/85">Status: {{ $order->statusLabel() }}. Keep this page's link to follow your order.</p>
    </section>

    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6">
        @if (session('status'))
            <p class="mb-6 rounded-xl bg-brand-50 px-5 py-4 font-medium text-brand-900" role="status">{{ session('status') }}</p>
        @endif
        @if (session('error'))
            <p class="mb-6 rounded-xl bg-red-50 px-5 py-4 font-medium text-red-800" role="alert">{{ session('error') }}</p>
        @endif

        <ul class="divide-y divide-slate-100 rounded-2xl border border-slate-200">
            @foreach ($order->items as $item)
                <li class="flex justify-between gap-4 px-5 py-3">
                    <span>{{ $item->quantity }} × {{ $item->product_name }}{{ $item->variant_name !== 'One size' ? ' ('.$item->variant_name.')' : '' }}</span>
                    <span class="font-semibold">{{ Product::money($item->lineTotalCents()) }}</span>
                </li>
            @endforeach
            @if ($order->delivery_fee_cents > 0)
                <li class="flex justify-between gap-4 px-5 py-3"><span>Delivery</span><span class="font-semibold">{{ Product::money($order->delivery_fee_cents) }}</span></li>
            @endif
            <li class="flex justify-between gap-4 px-5 py-3 font-display text-lg font-bold text-brand-900"><span>Total</span><span>{{ Product::money($order->total_cents) }}</span></li>
        </ul>

        <section class="mt-8 rounded-2xl border border-slate-200 p-6">
            <h2 class="font-display text-xl font-bold uppercase tracking-wide text-brand-900">{{ $order->fulfilment->label() }}</h2>
            @if ($order->fulfilment === Fulfilment::Delivery)
                <p class="mt-2 text-slate-700">{{ $order->delivery_address }}, {{ $order->delivery_city }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ config('shop.delivery_note') }}</p>
            @else
                <p class="mt-2 text-slate-700">{{ config('shop.pickup_note') }}</p>
            @endif
        </section>

        @unless ($order->isCancelled())
            <section class="mt-8 rounded-2xl border border-slate-200 p-6" aria-labelledby="payment-heading">
                <h2 id="payment-heading" class="font-display text-xl font-bold uppercase tracking-wide text-brand-900">Payment · {{ Product::money($order->total_cents) }}</h2>
                @if ($order->paid_at)
                    <p class="mt-3 font-semibold text-brand-700">✓ Paid{{ $method ? ' ('.$method->label().')' : '' }}. Thank you!</p>
                @elseif ($method === PaymentMethod::Online && PaymentMethod::Online->isAvailable())
                    <form method="POST" action="{{ route('orders.pay', $order) }}" class="mt-4">@csrf
                        <button class="rounded-full bg-accent-500 px-8 py-3 font-display font-bold uppercase tracking-wide text-white hover:bg-accent-600">Pay {{ Product::money($order->total_cents) }} now</button>
                    </form>
                @elseif ($method?->isWalletTransfer())
                    <ol class="mt-3 list-decimal space-y-1 pl-5 text-slate-700">
                        <li>Send <strong>{{ Product::money($order->total_cents) }}</strong> by <strong>{{ $method === PaymentMethod::Whish ? 'Whish Money' : 'OMT Pay' }}</strong> to
                            <strong>{{ $method->accountName() }}</strong>, <strong class="whitespace-nowrap">{{ $method->accountNumber() }}</strong>.</li>
                        <li>Write the reference <strong>{{ $order->reference }}</strong> in the transfer note.</li>
                        <li>Enter the transaction number below.</li>
                    </ol>
                    @if ($order->payment_reference)
                        <p class="mt-4 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-900">We received transaction number <strong>{{ $order->payment_reference }}</strong>. We'll confirm your payment soon.</p>
                    @endif
                    <form method="POST" action="{{ route('orders.transfer', $order) }}" class="mt-4 flex flex-wrap items-end gap-3">
                        @csrf
                        <div class="min-w-0 flex-1"><x-site.field name="payment_reference" label="Transaction number" maxlength="60" /></div>
                        <button class="rounded-full border border-brand-600 px-6 py-3 font-display font-semibold uppercase tracking-wide text-brand-700 hover:bg-brand-50">{{ $order->payment_reference ? 'Update' : 'Send' }}</button>
                    </form>
                @else
                    <p class="mt-3 text-slate-700">Pay <strong>{{ Product::money($order->total_cents) }}</strong> in cash {{ $order->fulfilment === Fulfilment::Delivery ? 'to the driver' : 'when you collect your order' }}.</p>
                @endif
            </section>
        @else
            <p class="mt-8 rounded-xl bg-slate-100 px-5 py-4 text-slate-700">This order was cancelled{{ $order->cancellation_reason ? ': '.$order->cancellation_reason : '.' }}
                {{ $order->paid_at ? "We'll refund your payment." : '' }}</p>
        @endunless

        <p class="mt-8 text-center"><a href="{{ route('shop.index') }}" class="font-display font-semibold uppercase tracking-wide text-brand-600 hover:text-brand-800">&larr; Back to the shop</a></p>
    </div>
</x-site.layout>
