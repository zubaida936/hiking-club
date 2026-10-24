<x-admin.layout title="New homepage slide">
    <form method="POST" action="{{ route('admin.hero-slides.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.hero-slides._form')
        <div class="mt-6 flex gap-2">
            <x-admin.button>Add slide</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.hero-slides.index')">Cancel</x-admin.button>
        </div>
    </form>
</x-admin.layout>
