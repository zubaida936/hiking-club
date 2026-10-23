<x-mail::message>
@if ($order->isCancelled())
# Your order was cancelled

Hi {{ $order->name }}, your order **{{ $order->reference }}** has been cancelled.
@if ($order->cancellation_reason)

**Reason:** {{ $order->cancellation_reason }}
@endif
@if ($order->paid_at)

**Refund:** you paid {{ \App\Models\Product::money($order->total_cents) }}. We'll pay it back and contact you on WhatsApp to arrange it.
@endif
@else
# {{ $order->statusLabel() }}

Hi {{ $order->name }}, your order **{{ $order->reference }}** is {{ strtolower($order->statusLabel()) }}.

{{ $order->fulfilment === \App\Domain\Shop\Enums\Fulfilment::Delivery ? config('shop.delivery_note') : config('shop.pickup_note') }}
@if ($order->awaitsPayment())

**Still to pay:** {{ \App\Models\Product::money($order->total_cents) }}.
@endif
@endif

<x-mail::button :url="$orderUrl">
View my order
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
