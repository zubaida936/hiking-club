<x-admin.layout title="Shop products">
    <div class="mb-4 flex flex-wrap items-end gap-3">
        <form method="GET" class="flex flex-wrap items-end gap-2">
            <input name="q" value="{{ request('q') }}" placeholder="Search products" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <select name="category" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) request('category') === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <x-admin.button variant="secondary">Filter</x-admin.button>
        </form>
        <div class="ml-auto flex gap-2">
            <x-admin.button variant="secondary" :href="route('admin.product-categories.index')">Categories</x-admin.button>
            <x-admin.button :href="route('admin.products.create')">+ New product</x-admin.button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Product</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Price</th>
                    <th class="px-4 py-3">Stock</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($products as $product)
                    @php($stock = $product->totalStock())
                    <tr>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if ($product->coverUrl())
                                    <img src="{{ $product->coverUrl() }}" alt="" class="size-10 rounded object-cover">
                                @else
                                    <div class="size-10 rounded bg-slate-200"></div>
                                @endif
                                <a href="{{ route('admin.products.edit', $product) }}" class="font-medium hover:underline">{{ $product->name }}</a>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ $product->category->name }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ \App\Models\Product::money($product->currentPriceCents()) }}
                            @if ($product->isOnSale())<span class="text-xs text-slate-400 line-through">{{ \App\Models\Product::money($product->price_cents) }}</span>@endif
                        </td>
                        <td @class(['px-4 py-3 font-semibold', 'text-red-600' => $stock <= (int) config('shop.low_stock')])>{{ $stock === 0 ? 'Out of stock' : $stock }}</td>
                        <td class="px-4 py-3">
                            <x-admin.badge :classes="$product->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700'">{{ $product->is_active ? 'In the shop' : 'Hidden' }}</x-admin.badge>
                        </td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.products.edit', $product) }}" class="font-medium text-emerald-700 hover:underline">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No products yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
</x-admin.layout>
