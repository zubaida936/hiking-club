<x-mail::message>
# New order {{ $order->reference }}

**{{ $order->name }}** · {{ $order->phone }}{{ $order->email ? ' · '.$order->email : '' }}

<x-mail::table>
| Item | Qty | Price |
|:--|:--:|--:|
@foreach ($order->items as $item)
| {{ $item->product_name }} ({{ $item->variant_name }}) | {{ $item->quantity }} | {{ \App\Models\Product::money($item->lineTotalCents()) }} |
@endforeach
| **Total** | | **{{ \App\Models\Product::money($order->total_cents) }}** |
</x-mail::table>

**{{ $order->fulfilment->label() }}**{{ $order->delivery_address ? ': '.$order->delivery_address.', '.$order->delivery_city : '' }}
· Payment: {{ $order->payment_method?->label() ?? '—' }}
@if ($order->notes)

Note: {{ $order->notes }}
@endif

<x-mail::button :url="$adminUrl">
Open in the CMS
</x-mail::button>
</x-mail::message>
