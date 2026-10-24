<x-admin.layout title="New article">
    <form method="POST" action="{{ route('admin.articles.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.articles._form')
        <div class="mt-6 flex gap-2">
            <x-admin.button>Create article</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.articles.index')">Cancel</x-admin.button>
        </div>
    </form>
</x-admin.layout>
