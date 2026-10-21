<x-admin.layout title="New event">
    <form method="POST" action="{{ route('admin.events.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.events._form')
        <div class="mt-6 flex gap-2">
            <x-admin.button>Create draft</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.events.index')">Cancel</x-admin.button>
        </div>
    </form>
</x-admin.layout>
