@php
    $variants = old('variants', $product->exists
        ? $product->variants->map(fn ($v) => ['id' => $v->id, 'name' => $v->name, 'stock' => $v->stock])->all()
        : [['id' => null, 'name' => 'One size', 'stock' => 0]]);
@endphp

<div class="grid gap-6 lg:grid-cols-3" x-data="{ variants: @js(array_values($variants)) }">
    <div class="space-y-6 lg:col-span-2">
        <section class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold">Product</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2"><x-admin.input name="name" label="Name" :value="$product->name" required maxlength="120" /></div>
                <div>
                    <label for="product_category_id" class="block text-sm font-medium text-slate-700">Category</label>
                    <select id="product_category_id" name="product_category_id" required class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('product_category_id', $product->product_category_id) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('product_category_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <x-admin.input name="slug" label="URL slug" :value="$product->slug" maxlength="140" hint="Leave empty to generate from the name." />
                <div class="sm:col-span-2">
                    <x-admin.textarea name="description" label="Description" :value="$product->description" rows="6" />
                </div>
                <div class="sm:col-span-2">
                    <x-admin.input name="tags" label="Tags" :value="implode(', ', $product->tags ?? [])" maxlength="300" hint="Comma separated, shown on the card, e.g. Waterproof, Breathable" />
                </div>
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold">Sizes and stock</h2>
                <button type="button" @click="variants.push({ id: null, name: '', stock: 0 })" class="text-sm font-medium text-emerald-700 hover:underline">+ Add size</button>
            </div>
            <p class="mb-3 text-xs text-slate-500">One row per size or colour. Products without sizes keep a single "One size" row.</p>
            <div class="space-y-2">
                <template x-for="(variant, i) in variants" :key="i">
                    <div class="flex items-center gap-2">
                        <input type="hidden" :name="`variants[${i}][id]`" :value="variant.id ?? ''">
                        <input type="text" :name="`variants[${i}][name]`" x-model="variant.name" placeholder="e.g. M or 42" maxlength="60"
                               class="min-w-0 flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm" aria-label="Size">
                        <input type="number" :name="`variants[${i}][stock]`" x-model.number="variant.stock" min="0"
                               class="w-28 rounded-md border border-slate-300 px-3 py-2 text-sm" aria-label="Stock">
                        <button type="button" @click="variants.splice(i, 1)" x-show="variants.length > 1" class="px-1 text-red-600" aria-label="Remove size">✕</button>
                    </div>
                </template>
            </div>
            @if ($errors->has('variants') || $errors->has('variants.*'))
                <ul class="mt-2 text-xs text-red-600">
                    @foreach (array_unique(array_merge($errors->get('variants'), ...array_values($errors->get('variants.*')))) as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <div class="space-y-6">
        <section class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold">Price</h2>
            <div class="space-y-4">
                <x-admin.input name="price" label="Price (USD)" type="number" step="0.01" min="0" :value="$product->exists ? number_format($product->price_cents / 100, 2, '.', '') : ''" required />
                <x-admin.input name="sale_price" label="Sale price (USD)" type="number" step="0.01" min="0"
                               :value="$product->sale_price_cents !== null ? number_format($product->sale_price_cents / 100, 2, '.', '') : ''" hint="Optional. Shown with the original price struck through." />
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" @checked(old('is_active', $product->is_active))> Show in the shop
                </label>
            </div>
        </section>

        <section class="rounded-lg bg-white p-6 shadow-sm">
            <h2 class="mb-4 font-semibold">Photos</h2>
            @if ($product->exists && $product->images->isNotEmpty())
                <div class="mb-4 grid grid-cols-3 gap-2">
                    @foreach ($product->images as $image)
                        <label class="relative block">
                            <img src="{{ $image->url() }}" alt="" class="aspect-square w-full rounded object-cover">
                            <span class="mt-1 flex items-center gap-1 text-xs text-slate-600">
                                <input type="checkbox" name="remove_images[]" value="{{ $image->id }}" class="rounded border-slate-300"> Remove
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif
            <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp"
                   class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm">
            <p class="mt-1 text-xs text-slate-500">JPG, PNG or WebP, max 5 MB each. The first photo is the card picture.</p>
            @error('images.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </section>
    </div>
</div>
