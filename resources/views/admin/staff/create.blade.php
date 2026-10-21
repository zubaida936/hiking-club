<x-admin.layout title="New staff user">
    <form method="POST" action="{{ route('admin.staff.store') }}" class="max-w-3xl rounded-lg bg-white p-6 shadow-sm">
        @csrf
        @include('admin.staff._form')
        <div class="mt-6 flex gap-2">
            <x-admin.button>Create</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.staff.index')">Cancel</x-admin.button>
        </div>
    </form>
</x-admin.layout>
