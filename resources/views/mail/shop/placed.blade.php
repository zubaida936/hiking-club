<x-mail::message>
# Thanks for your order!

Hi {{ $order->name }}, we received your order **{{ $order->reference }}**.

<x-mail::table>
| Item | Qty | Price |
|:--|:--:|--:|
@foreach ($order->items as $item)
| {{ $item->product_name }}{{ $item->variant_name !== 'One size' ? ' ('.$item->variant_name.')' : '' }} | {{ $item->quantity }} | {{ \App\Models\Product::money($item->lineTotalCents()) }} |
@endforeach
@if ($order->delivery_fee_cents > 0)
| Delivery | | {{ \App\Models\Product::money($order->delivery_fee_cents) }} |
@endif
| **Total** | | **{{ \App\Models\Product::money($order->total_cents) }}** |
</x-mail::table>

**{{ $order->fulfilment->label() }}:** {{ $order->fulfilment === \App\Domain\Shop\Enums\Fulfilment::Delivery ? $order->delivery_address.', '.$order->delivery_city.'. '.config('shop.delivery_note') : config('shop.pickup_note') }}

@if ($order->awaitsPayment())
@switch($order->payment_method?->value)
@case('whish')
@case('omt')
**Payment:** send {{ \App\Models\Product::money($order->total_cents) }} by {{ $order->payment_method->label() }} to {{ $order->payment_method->accountName() }}, {{ $order->payment_method->accountNumber() }}, with the reference {{ $order->reference }} in the note.
@break
@case('online')
**Payment:** pay online from your order page.
@break
@default
**Payment:** {{ \App\Models\Product::money($order->total_cents) }} in cash {{ $order->fulfilment === \App\Domain\Shop\Enums\Fulfilment::Delivery ? 'to the driver' : 'when you collect it' }}.
@endswitch
@endif

<x-mail::button :url="$orderUrl">
View my order
</x-mail::button>

{{ config('app.name') }}
</x-mail::message>
