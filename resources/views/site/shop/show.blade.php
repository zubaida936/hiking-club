@php
    $images = $product->images;
    $variants = $product->variants;
    $single = $variants->count() === 1;
    $inStock = $variants->sum('stock') > 0;
    $firstAvailable = $variants->first(fn ($v) => $v->stock > 0);
@endphp

<x-site.layout :title="$product->name" :description="Str::limit(strip_tags((string) $product->description), 160)" :image="$product->coverUrl()">
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <a href="{{ route('shop.index', ['category' => $product->category->slug]) }}" class="font-display text-sm font-semibold uppercase tracking-wide text-brand-600 hover:text-brand-800">&larr; {{ $product->category->name }}</a>

        @foreach (['success' => 'bg-brand-50 text-brand-900', 'error' => 'bg-red-50 text-red-800'] as $key => $classes)
            @if (session($key))
                <p class="mt-6 rounded-xl px-5 py-4 font-medium {{ $classes }}" role="{{ $key === 'error' ? 'alert' : 'status' }}">
                    {{ session($key) }}
                    @if ($key === 'success') <a href="{{ route('cart') }}" class="ml-2 font-bold underline">View cart</a> @endif
                </p>
            @endif
        @endforeach

        <div class="mt-6 grid gap-10 md:grid-cols-2">
            {{-- Gallery --}}
            <div x-data="{ current: 0 }">
                <div class="relative aspect-square overflow-hidden rounded-2xl bg-slate-100">
                    @forelse ($images as $i => $image)
                        <img src="{{ $image->url() }}" alt="{{ $product->name }}{{ $images->count() > 1 ? ' ('.($i + 1).' of '.$images->count().')' : '' }}"
                             width="1200" height="1200" @if ($i > 0) loading="lazy" @endif
                             class="absolute inset-0 size-full object-cover" x-show="current === {{ $i }}" @if ($i > 0) x-cloak @endif>
                    @empty
                        <div class="absolute inset-0 flex items-center justify-center text-7xl text-slate-300" aria-hidden="true">🎒</div>
                    @endforelse
                </div>
                @if ($images->count() > 1)
                    <div class="mt-3 flex gap-2 overflow-x-auto">
                        @foreach ($images as $i => $image)
                            <button type="button" @click="current = {{ $i }}" :aria-pressed="(current === {{ $i }}).toString()"
                                    class="size-16 shrink-0 overflow-hidden rounded-lg ring-2" :class="current === {{ $i }} ? 'ring-brand-600' : 'ring-transparent'">
                                <img src="{{ $image->url() }}" alt="Photo {{ $i + 1 }}" class="size-full object-cover" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Details --}}
            <div>
                <div class="flex flex-wrap gap-2">
                    @if ($product->isOnSale())
                        <span class="rounded-full bg-red-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">Sale</span>
                    @endif
                    @foreach ($product->tags ?? [] as $tag)
                        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-800">{{ $tag }}</span>
                    @endforeach
                </div>
                <h1 class="mt-3 font-display text-3xl font-bold uppercase tracking-wide text-brand-900 sm:text-4xl">{{ $product->name }}</h1>
                <p class="mt-3 flex items-baseline gap-3">
                    <span class="font-display text-3xl font-bold text-brand-900">{{ \App\Models\Product::money($product->currentPriceCents()) }}</span>
                    @if ($product->isOnSale())
                        <span class="text-lg text-slate-400 line-through">{{ \App\Models\Product::money($product->price_cents) }}</span>
                    @endif
                </p>

                @if ($inStock)
                    <form method="POST" action="{{ route('cart.add') }}" class="mt-8 space-y-5">
                        @csrf
                        @if ($single)
                            <input type="hidden" name="variant_id" value="{{ $variants->first()->id }}">
                            <p class="text-sm font-semibold {{ $variants->first()->stock <= (int) config('shop.low_stock') ? 'text-red-600' : 'text-brand-700' }}">
                                {{ $variants->first()->stock <= (int) config('shop.low_stock') ? 'Only '.$variants->first()->stock.' left' : 'In stock' }}
                            </p>
                        @else
                            <fieldset>
                                <legend class="text-sm font-semibold text-slate-700">Size</legend>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($variants as $variant)
                                        <label @class(['cursor-pointer rounded-xl border px-4 py-2 text-sm has-checked:border-brand-600 has-checked:bg-brand-50', 'border-slate-300' => $variant->stock > 0, 'cursor-not-allowed border-slate-200 text-slate-400 line-through' => $variant->stock === 0])>
                                            <input type="radio" name="variant_id" value="{{ $variant->id }}" class="sr-only" @disabled($variant->stock === 0) @checked($firstAvailable?->is($variant))>
                                            {{ $variant->name }}
                                            @if ($variant->stock > 0 && $variant->stock <= (int) config('shop.low_stock'))
                                                <span class="text-xs text-red-600">({{ $variant->stock }} left)</span>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>
                                @error('variant_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </fieldset>
                        @endif

                        <div class="flex items-end gap-3">
                            <div>
                                <label for="quantity" class="block text-sm font-semibold text-slate-700">Quantity</label>
                                <input id="quantity" name="quantity" type="number" min="1" max="{{ config('shop.max_quantity') }}" value="1" inputmode="numeric"
                                       class="mt-1 w-24 rounded-xl border border-slate-300 px-3 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            </div>
                            <button class="flex-1 rounded-full bg-accent-500 px-8 py-3 font-display font-bold uppercase tracking-wide text-white hover:bg-accent-600">Add to cart</button>
                        </div>
                    </form>
                @else
                    <p class="mt-8 rounded-xl bg-slate-100 px-5 py-4 font-semibold text-slate-600">Out of stock. Message us on WhatsApp to know when it's back.</p>
                @endif

                @if ($product->description)
                    <div class="mt-10 space-y-3 leading-relaxed text-slate-700">
                        @foreach (preg_split('/\R{2,}/', trim($product->description)) ?: [] as $paragraph)
                            <p>{!! nl2br(e(trim($paragraph))) !!}</p>
                        @endforeach
                    </div>
                @endif

                <p class="mt-8 text-sm text-slate-500">{{ config('shop.pickup_note') }}</p>
            </div>
        </div>
    </div>
</x-site.layout>
