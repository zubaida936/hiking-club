<x-admin.layout title="Edit homepage slide">
    <form method="POST" action="{{ route('admin.hero-slides.update', $slide) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.hero-slides._form')
        <div class="mt-6 flex flex-wrap gap-2">
            <x-admin.button>Save slide</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.hero-slides.index')">Back to slides</x-admin.button>
        </div>
    </form>
    <form method="POST" action="{{ route('admin.hero-slides.destroy', $slide) }}" class="mt-6" onsubmit="return confirm('Delete this slide?')">
        @csrf @method('DELETE')
        <x-admin.button variant="danger">Delete slide</x-admin.button>
    </form>
</x-admin.layout>
