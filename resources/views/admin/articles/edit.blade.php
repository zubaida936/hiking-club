<x-admin.layout :title="$article->title">
    <form method="POST" action="{{ route('admin.articles.update', $article) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.articles._form')
        <div class="mt-6 flex flex-wrap gap-2">
            <x-admin.button>Save article</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.articles.index')">Back to articles</x-admin.button>
            <x-admin.button variant="secondary" :href="route('guide.articles.show', $article->slug)" target="_blank">{{ $article->isPublished() ? 'View on the site' : 'Preview' }}</x-admin.button>
        </div>
    </form>
    <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" class="mt-6"
          onsubmit="return confirm('Delete this article and its cover photo?')">
        @csrf @method('DELETE')
        <x-admin.button variant="danger">Delete article</x-admin.button>
    </form>
</x-admin.layout>
