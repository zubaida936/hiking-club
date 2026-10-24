<x-admin.layout :title="$album->title">
    <form method="POST" action="{{ route('admin.albums.update', $album) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.albums._form')
        <div class="mt-6 flex flex-wrap gap-2">
            <x-admin.button>Save album</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.albums.index')">Back to gallery</x-admin.button>
            <x-admin.button variant="secondary" :href="route('gallery.show', $album->slug)" target="_blank">{{ $album->is_published ? 'View on the site' : 'Preview' }}</x-admin.button>
        </div>
    </form>
    <form method="POST" action="{{ route('admin.albums.destroy', $album) }}" class="mt-6"
          onsubmit="return confirm('Delete this album and all {{ $album->photos->count() }} photos?')">
        @csrf @method('DELETE')
        <x-admin.button variant="danger">Delete album</x-admin.button>
    </form>
</x-admin.layout>
