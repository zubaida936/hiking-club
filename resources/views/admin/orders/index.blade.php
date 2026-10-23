<x-admin.layout title="Shop orders">
    <div class="mb-4 flex flex-wrap items-center gap-2 text-sm">
        <a href="{{ route('admin.orders.index') }}"
           @class(['rounded-md px-3 py-1.5 font-medium', 'bg-slate-800 text-white' => ! request('status'), 'bg-white text-slate-700 shadow-sm hover:bg-slate-50' => request('status')])>All ({{ $openCount }} open)</a>
        @foreach ($statuses as $status)
            <a href="{{ route('admin.orders.index', ['status' => $status->value]) }}"
               @class(['rounded-md px-3 py-1.5 font-medium', 'bg-slate-800 text-white' => request('status') === $status->value, 'bg-white text-slate-700 shadow-sm hover:bg-slate-50' => request('status') !== $status->value])>{{ $status->label() }}</a>
        @endforeach
        <form method="GET" class="ml-auto flex gap-2">
            @if (request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <input name="q" value="{{ request('q') }}" placeholder="Name, phone or reference" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm">
            <x-admin.button variant="secondary">Search</x-admin.button>
        </form>
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Items</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3">Payment</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($orders as $order)
                    <tr class="align-top">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-mono font-medium hover:underline">{{ $order->reference }}</a>
                            <span class="block text-xs text-slate-500">{{ $order->created_at->diffForHumans() }} · {{ $order->fulfilment->label() }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $order->name }}<span class="block text-xs text-slate-500">{{ $order->phone }}</span></td>
                        <td class="px-4 py-3">{{ $order->itemCount() }}</td>
                        <td class="px-4 py-3 font-semibold">{{ \App\Models\Product::money($order->total_cents) }}</td>
                        <td class="px-4 py-3 text-xs">
                            @if ($order->paid_at)
                                <span class="font-semibold text-emerald-700">✓ Paid</span>
                            @elseif ($order->isCancelled())
                                <span class="text-slate-400">—</span>
                            @else
                                <span class="text-slate-500">Unpaid</span>
                            @endif
                            <span class="block text-slate-500">{{ $order->payment_method?->label() }}</span>
                            @if ($order->isCancelled() && $order->paid_at)<span class="block font-bold text-red-600">Refund due</span>@endif
                        </td>
                        <td class="px-4 py-3"><x-admin.badge :classes="$order->status->badgeClasses()">{{ $order->statusLabel() }}</x-admin.badge></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No orders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>
</x-admin.layout>
