<x-admin.layout title="Shop categories">
    <div class="max-w-2xl space-y-6">
        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Order</th><th class="px-4 py-3">Products</th><th class="px-4 py-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($categories as $category)
                        <tr>
                            <td class="px-4 py-2" colspan="2">
                                <form method="POST" action="{{ route('admin.product-categories.update', $category) }}" class="flex items-center gap-2">
                                    @csrf @method('PUT')
                                    <input name="name" value="{{ $category->name }}" maxlength="60" required aria-label="Name" class="min-w-0 flex-1 rounded-md border border-slate-300 px-2 py-1">
                                    <input name="position" type="number" min="0" value="{{ $category->position }}" aria-label="Order" class="w-20 rounded-md border border-slate-300 px-2 py-1">
                                    <button class="text-xs font-medium text-emerald-700 hover:underline">Save</button>
                                </form>
                            </td>
                            <td class="px-4 py-2">{{ $category->products_count }}</td>
                            <td class="px-4 py-2 text-right">
                                @if ($category->products_count === 0)
                                    <form method="POST" action="{{ route('admin.product-categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                                        @csrf @method('DELETE')
                                        <button class="text-xs font-medium text-red-600 hover:underline">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('admin.product-categories.store') }}" class="flex items-end gap-2 rounded-lg bg-white p-4 shadow-sm">
            @csrf
            <div class="flex-1"><x-admin.input name="name" label="New category" maxlength="60" required /></div>
            <x-admin.button>Add</x-admin.button>
        </form>

        <x-admin.button variant="secondary" :href="route('admin.products.index')">Back to products</x-admin.button>
    </div>
</x-admin.layout>
