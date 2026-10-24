<x-admin.layout title="New album">
    <form method="POST" action="{{ route('admin.albums.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.albums._form')
        <div class="mt-6 flex gap-2">
            <x-admin.button>Create album</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.albums.index')">Cancel</x-admin.button>
        </div>
    </form>
</x-admin.layout>
