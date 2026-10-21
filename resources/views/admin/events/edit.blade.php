<x-admin.layout :title="$event->title">
    {{-- Status bar & lifecycle actions --}}
    <div class="mb-6 flex flex-wrap items-center gap-3 rounded-lg bg-white p-4 shadow-sm" x-data="{ cancelOpen: @js($errors->has('cancellation_reason')) }">
        <x-admin.badge :classes="$event->status->badgeClasses()">{{ $event->status->label() }}</x-admin.badge>
        <span class="text-sm text-slate-500">
            @if ($event->cancelled_at)
                Cancelled {{ $event->cancelled_at->diffForHumans() }}: {{ $event->cancellation_reason }}
            @elseif ($event->published_at && $event->isPublished())
                Published {{ $event->published_at->diffForHumans() }}
            @else
                Not visible on the website
            @endif
            · Last edited {{ $event->updated_at->diffForHumans() }}{{ $event->editor ? ' by '.$event->editor->name : '' }}
        </span>

        <div class="ml-auto flex flex-wrap gap-2">
            @if ($event->status === \App\Domain\Events\Enums\EventStatus::Draft)
                <form method="POST" action="{{ route('admin.events.publish', $event) }}">@csrf
                    <x-admin.button>Publish</x-admin.button>
                </form>
            @elseif ($event->isPublished())
                <form method="POST" action="{{ route('admin.events.unpublish', $event) }}">@csrf
                    <x-admin.button variant="secondary">Unpublish</x-admin.button>
                </form>
            @endif

            <form method="POST" action="{{ route('admin.events.duplicate', $event) }}">@csrf
                <x-admin.button variant="secondary">Duplicate</x-admin.button>
            </form>

            @if ($event->status !== \App\Domain\Events\Enums\EventStatus::Cancelled)
                <x-admin.button variant="secondary" type="button" @click="cancelOpen = true">Cancel event</x-admin.button>
            @endif

            @unless ($event->isPublished())
                <form method="POST" action="{{ route('admin.events.destroy', $event) }}"
                      onsubmit="return confirm('Delete this event? It can be restored by a developer.')">@csrf @method('DELETE')
                    <x-admin.button variant="danger">Delete</x-admin.button>
                </form>
            @endunless
        </div>

        {{-- Cancel dialog --}}
        <div x-show="cancelOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" @keydown.escape.window="cancelOpen = false">
            <form method="POST" action="{{ route('admin.events.cancel', $event) }}" class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl" @click.outside="cancelOpen = false">
                @csrf
                <h2 class="text-lg font-semibold">Cancel this event?</h2>
                <p class="mt-1 text-sm text-slate-500">The reason is shown to hikers. This can't be undone; duplicate the event to reschedule.</p>
                <div class="mt-4">
                    <x-admin.textarea name="cancellation_reason" label="Reason" rows="3" required maxlength="500" />
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <x-admin.button variant="secondary" type="button" @click="cancelOpen = false">Keep event</x-admin.button>
                    <x-admin.button variant="danger">Cancel event</x-admin.button>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.events.update', $event) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.events._form')
        <div class="mt-6 flex gap-2">
            <x-admin.button>Save changes</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.events.index')">Back to events</x-admin.button>
        </div>
    </form>
</x-admin.layout>
