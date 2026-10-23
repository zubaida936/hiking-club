<x-admin.layout :title="$product->name">
    <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.products._form')
        <div class="mt-6 flex flex-wrap gap-2">
            <x-admin.button>Save product</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.products.index')">Back to products</x-admin.button>
            @if ($product->is_active)
                <x-admin.button variant="secondary" :href="route('shop.show', $product)" target="_blank">View in the shop</x-admin.button>
            @endif
        </div>
    </form>
    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="mt-6"
          onsubmit="return confirm('Delete this product? Past orders keep their details.')">
        @csrf @method('DELETE')
        <x-admin.button variant="danger">Delete product</x-admin.button>
    </form>
</x-admin.layout>
