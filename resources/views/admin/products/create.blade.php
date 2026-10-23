<x-admin.layout title="New product">
    <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.products._form')
        <div class="mt-6 flex gap-2">
            <x-admin.button>Create product</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.products.index')">Cancel</x-admin.button>
        </div>
    </form>
</x-admin.layout>
