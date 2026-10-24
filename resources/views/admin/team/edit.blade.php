<x-admin.layout :title="$member->name">
    <form method="POST" action="{{ route('admin.team.update', $member) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.team._form')
        <div class="mt-6 flex flex-wrap gap-2">
            <x-admin.button>Save team member</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.team.index')">Back to team</x-admin.button>
        </div>
    </form>
    <form method="POST" action="{{ route('admin.team.destroy', $member) }}" class="mt-6" onsubmit="return confirm('Remove this person from the team?')">
        @csrf @method('DELETE')
        <x-admin.button variant="danger">Remove from team</x-admin.button>
    </form>
</x-admin.layout>
