<x-admin.layout :title="'Order '.$order->reference">
    <div class="grid gap-6 lg:grid-cols-3" x-data="{ cancelOpen: false }">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-lg bg-white p-6 shadow-sm">
                <h2 class="mb-3 font-semibold">Items</h2>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="py-2">{{ $item->product_name }} <span class="text-slate-500">({{ $item->variant_name }})</span></td>
                                <td class="py-2 text-right">{{ $item->quantity }} × {{ \App\Models\Product::money($item->unit_price_cents) }}</td>
                                <td class="py-2 text-right font-semibold">{{ \App\Models\Product::money($item->lineTotalCents()) }}</td>
                            </tr>
                        @endforeach
                        @if ($order->delivery_fee_cents > 0)
                            <tr><td class="py-2" colspan="2">Delivery</td><td class="py-2 text-right">{{ \App\Models\Product::money($order->delivery_fee_cents) }}</td></tr>
                        @endif
                        <tr class="font-semibold"><td class="py-2" colspan="2">Total</td><td class="py-2 text-right">{{ \App\Models\Product::money($order->total_cents) }}</td></tr>
                    </tbody>
                </table>
            </section>

            <section class="rounded-lg bg-white p-6 shadow-sm text-sm">
                <h2 class="mb-3 font-semibold">Customer</h2>
                <p>{{ $order->name }} · <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="text-emerald-700 hover:underline">{{ $order->phone }} (WhatsApp)</a>{{ $order->email ? ' · '.$order->email : '' }}</p>
                <p class="mt-2"><strong>{{ $order->fulfilment->label() }}</strong>{{ $order->delivery_address ? ': '.$order->delivery_address.', '.$order->delivery_city : '' }}</p>
                @if ($order->notes)<p class="mt-2 text-amber-800">📝 {{ $order->notes }}</p>@endif
                <p class="mt-2 text-xs text-slate-500">Placed {{ $order->created_at->setTimezone(config('site.timezone'))->format('M d, Y H:i') }}</p>
            </section>
        </div>

        <div class="space-y-6">
            <section class="rounded-lg bg-white p-6 shadow-sm">
                <x-admin.badge :classes="$order->status->badgeClasses()">{{ $order->statusLabel() }}</x-admin.badge>
                @if ($order->isCancelled() && $order->cancellation_reason)<p class="mt-2 text-xs text-slate-500">{{ $order->cancellation_reason }}</p>@endif

                @if ($order->status->next() !== [])
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($order->status->next() as $next)
                            <form method="POST" action="{{ route('admin.orders.status', $order) }}">@csrf
                                <input type="hidden" name="status" value="{{ $next->value }}">
                                <x-admin.button :variant="$loop->first ? 'primary' : 'secondary'">{{ $next->label($order->fulfilment) }}</x-admin.button>
                            </form>
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-slate-500">The customer is emailed when the order is {{ strtolower(\App\Domain\Shop\Enums\OrderStatus::Ready->label($order->fulfilment)) }} or cancelled.</p>
                @endif
            </section>

            <section class="rounded-lg bg-white p-6 shadow-sm text-sm">
                <h2 class="mb-2 font-semibold">Payment</h2>
                <p>{{ $order->payment_method?->label() ?? 'Not chosen' }}</p>
                @if ($order->payment_reference)<p class="font-mono text-xs">#{{ $order->payment_reference }}</p>@endif
                <p class="mt-2 font-semibold {{ $order->paid_at ? 'text-emerald-700' : 'text-slate-500' }}">{{ $order->paid_at ? '✓ Paid '.$order->paid_at->diffForHumans() : 'Unpaid' }}</p>
                @if ($order->isCancelled() && $order->paid_at)<p class="mt-1 font-bold text-red-600">Refund due</p>@endif
                @unless ($order->isCancelled())
                    <form method="POST" action="{{ route('admin.orders.payment', $order) }}" class="mt-3">@csrf
                        <input type="hidden" name="paid" value="{{ $order->paid_at ? 0 : 1 }}">
                        <x-admin.button variant="secondary">{{ $order->paid_at ? 'Mark unpaid' : 'Mark paid' }}</x-admin.button>
                    </form>
                @endunless
            </section>

            @if (! in_array($order->status, [\App\Domain\Shop\Enums\OrderStatus::Completed, \App\Domain\Shop\Enums\OrderStatus::Cancelled], true))
                <x-admin.button variant="danger" type="button" @click="cancelOpen = true">Cancel order</x-admin.button>
            @endif
        </div>

        <div x-show="cancelOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" @keydown.escape.window="cancelOpen = false">
            <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl" @click.outside="cancelOpen = false">
                @csrf
                <h2 class="text-lg font-semibold">Cancel order {{ $order->reference }}?</h2>
                <p class="mt-1 text-sm text-slate-500">The items go back in stock.{{ $order->paid_at ? ' It is paid: refund the customer yourself.' : '' }}</p>
                <div class="mt-4"><x-admin.textarea name="cancellation_reason" label="Reason (shown to the customer)" rows="3" maxlength="500" /></div>
                <label class="mt-3 flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="notify" value="0">
                    <input type="checkbox" name="notify" value="1" checked class="rounded border-slate-300"> Email the customer
                </label>
                <div class="mt-4 flex justify-end gap-2">
                    <x-admin.button variant="secondary" type="button" @click="cancelOpen = false">Keep order</x-admin.button>
                    <x-admin.button variant="danger">Cancel order</x-admin.button>
                </div>
            </form>
        </div>
    </div>
</x-admin.layout>
