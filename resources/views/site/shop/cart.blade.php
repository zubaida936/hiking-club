<x-site.layout title="Cart" :noindex="true">
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <h1 class="font-display text-3xl font-bold uppercase tracking-wide text-brand-900">Your cart</h1>

        @foreach (['success' => 'bg-brand-50 text-brand-900', 'error' => 'bg-red-50 text-red-800'] as $key => $classes)
            @if (session($key))
                <p class="mt-6 rounded-xl px-5 py-4 font-medium {{ $classes }}" role="{{ $key === 'error' ? 'alert' : 'status' }}">{{ session($key) }}</p>
            @endif
        @endforeach

        @if ($lines->isEmpty())
            <p class="mt-8 rounded-2xl bg-slate-50 p-10 text-center text-slate-600">Your cart is empty.
                <a href="{{ route('shop.index') }}" class="font-semibold text-brand-700 underline">Browse the shop</a></p>
        @else
            <form method="POST" action="{{ route('cart.update') }}" class="mt-8">
                @csrf
                <ul class="divide-y divide-slate-100 rounded-2xl border border-slate-200">
                    @foreach ($lines as $line)
                        @php
                            $variant = $line['variant'];
                            $product = $variant->product;
                        @endphp
                        <li class="flex flex-wrap items-center gap-4 p-4">
                            <div class="size-16 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                                @if ($product->coverUrl())<img src="{{ $product->coverUrl() }}" alt="" class="size-full object-cover">@endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('shop.show', $product) }}" class="font-semibold text-brand-900 hover:underline">{{ $product->name }}</a>
                                @if ($variant->name !== 'One size')<span class="block text-sm text-slate-500">Size: {{ $variant->name }}</span>@endif
                                <span class="block text-sm text-slate-500">{{ \App\Models\Product::money($line['unit_cents']) }} each</span>
                                @unless ($line['in_stock'])
                                    <span class="block text-sm font-semibold text-red-600">{{ $variant->stock === 0 ? 'Sold out: please remove it.' : 'Only '.$variant->stock.' left: lower the quantity.' }}</span>
                                @endunless
                            </div>
                            <label class="sr-only" for="qty-{{ $variant->id }}">Quantity of {{ $product->name }}</label>
                            <input id="qty-{{ $variant->id }}" name="quantities[{{ $variant->id }}]" type="number" min="0" max="{{ config('shop.max_quantity') }}" value="{{ $line['quantity'] }}"
                                   class="w-20 rounded-xl border border-slate-300 px-3 py-2 text-center">
                            <span class="w-20 text-right font-semibold">{{ \App\Models\Product::money($line['line_cents']) }}</span>
                            <button type="submit" form="remove-{{ $variant->id }}" class="text-sm text-red-600 hover:underline">Remove</button>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
                    <button class="rounded-full border border-brand-600 px-6 py-2 font-display text-sm font-semibold uppercase tracking-wide text-brand-700 hover:bg-brand-50">Update quantities</button>
                    <p class="text-lg">Subtotal: <strong class="font-display text-2xl text-brand-900">{{ \App\Models\Product::money($subtotal) }}</strong></p>
                </div>
            </form>

            @foreach ($lines as $line)
                <form id="remove-{{ $line['variant']->id }}" method="POST" action="{{ route('cart.remove', $line['variant']) }}" class="hidden">@csrf</form>
            @endforeach

            <div class="mt-8 flex flex-wrap justify-end gap-3">
                <a href="{{ route('shop.index') }}" class="rounded-full px-6 py-3 font-display font-semibold uppercase tracking-wide text-brand-700 hover:bg-brand-50">Continue shopping</a>
                @if ($allInStock)
                    <a href="{{ route('checkout') }}" class="rounded-full bg-accent-500 px-8 py-3 font-display font-bold uppercase tracking-wide text-white hover:bg-accent-600">Checkout</a>
                @else
                    <span class="rounded-full bg-slate-200 px-8 py-3 font-display font-bold uppercase tracking-wide text-slate-500">Checkout</span>
                @endif
            </div>
        @endif
    </div>
</x-site.layout>
