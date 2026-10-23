@props(['product'])

@php($stock = $product->totalStock())
<article {{ $attributes->class('relative flex flex-col overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-slate-200') }}>
    <div class="relative aspect-square bg-slate-100">
        @if ($product->coverUrl())
            <img src="{{ $product->coverUrl() }}" alt="{{ $product->name }}" width="800" height="800" loading="lazy" decoding="async"
                 @class(['absolute inset-0 size-full object-cover', 'opacity-50 grayscale' => $stock === 0])>
        @else
            <div class="absolute inset-0 flex items-center justify-center text-5xl text-slate-300" aria-hidden="true">🎒</div>
        @endif
        <div class="absolute inset-x-3 top-3 flex flex-wrap gap-2">
            @if ($product->isOnSale())
                <span class="rounded-full bg-red-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">Sale</span>
            @endif
            @foreach ($product->tags ?? [] as $tag)
                <span class="rounded-full bg-white/90 px-3 py-1 text-xs font-medium text-brand-800">{{ $tag }}</span>
            @endforeach
        </div>
    </div>
    <div class="flex flex-1 flex-col p-4">
        <p class="text-xs uppercase tracking-wide text-slate-500">{{ $product->category->name }}</p>
        <h3 class="mt-1 font-display text-lg font-bold text-brand-900">
            <a href="{{ route('shop.show', $product) }}" class="after:absolute after:inset-0">{{ $product->name }}</a>
        </h3>
        <p class="mt-2 flex items-baseline gap-2">
            <span class="font-display text-xl font-bold text-brand-900">{{ \App\Models\Product::money($product->currentPriceCents()) }}</span>
            @if ($product->isOnSale())
                <span class="text-sm text-slate-400 line-through">{{ \App\Models\Product::money($product->price_cents) }}</span>
            @endif
        </p>
        <p class="mt-auto pt-3 text-sm font-semibold">
            @if ($stock === 0)
                <span class="text-slate-500">Out of stock</span>
            @elseif ($stock <= (int) config('shop.low_stock'))
                <span class="text-red-600">Only {{ $stock }} left</span>
            @else
                <span class="text-brand-700">In stock</span>
            @endif
        </p>
    </div>
</article>
