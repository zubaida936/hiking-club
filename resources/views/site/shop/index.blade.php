<x-site.layout title="Shop" description="Hiking gear and club apparel: backpacks, jackets, footwear, clothing and accessories. Pick up at a hike or get it delivered.">
    <section class="bg-brand-800 px-4 py-16 text-center text-white sm:px-6">
        <p class="font-display text-sm font-semibold uppercase tracking-[0.2em] text-accent-400">Gear up</p>
        <h1 class="mt-2 font-display text-4xl font-bold uppercase tracking-wide sm:text-5xl">Shop</h1>
        <p class="mx-auto mt-4 max-w-2xl text-white/80">Club apparel and trail-tested gear. Collect it at your next hike or get it delivered.</p>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <nav aria-label="Categories" class="mb-8 flex flex-wrap justify-center gap-2">
            <a href="{{ route('shop.index') }}"
               @class(['rounded-full px-4 py-2 text-sm font-semibold', 'bg-brand-700 text-white' => ! $category, 'bg-slate-100 text-slate-700 hover:bg-slate-200' => $category])
               @if (! $category) aria-current="page" @endif>All</a>
            @foreach ($categories as $item)
                <a href="{{ route('shop.index', ['category' => $item->slug]) }}"
                   @class(['rounded-full px-4 py-2 text-sm font-semibold', 'bg-brand-700 text-white' => $category?->is($item), 'bg-slate-100 text-slate-700 hover:bg-slate-200' => ! $category?->is($item)])
                   @if ($category?->is($item)) aria-current="page" @endif>{{ $item->name }}</a>
            @endforeach
        </nav>

        @if ($products->isEmpty())
            <p class="rounded-2xl bg-slate-50 p-10 text-center text-slate-600">No products here yet. Check back soon!</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-site.product-card :product="$product" />
                @endforeach
            </div>
        @endif
    </div>
</x-site.layout>
