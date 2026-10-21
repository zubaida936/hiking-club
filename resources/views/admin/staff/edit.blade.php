<x-admin.layout :title="'Edit '.$user->name">
    <form method="POST" action="{{ route('admin.staff.update', $user) }}" class="max-w-3xl rounded-lg bg-white p-6 shadow-sm">
        @csrf @method('PUT')
        @include('admin.staff._form')
        <div class="mt-6 flex gap-2">
            <x-admin.button>Save changes</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.staff.index')">Cancel</x-admin.button>
        </div>
    </form>
</x-admin.layout>
