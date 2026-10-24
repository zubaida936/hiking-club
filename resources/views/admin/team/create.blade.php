<x-admin.layout title="New team member">
    <form method="POST" action="{{ route('admin.team.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.team._form')
        <div class="mt-6 flex gap-2">
            <x-admin.button>Add team member</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.team.index')">Cancel</x-admin.button>
        </div>
    </form>
</x-admin.layout>
