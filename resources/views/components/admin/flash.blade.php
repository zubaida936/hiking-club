@foreach (['success' => 'border-emerald-300 bg-emerald-50 text-emerald-800', 'error' => 'border-red-300 bg-red-50 text-red-800'] as $key => $classes)
    @if (session($key))
        <div role="alert" class="mb-4 rounded-md border px-4 py-3 text-sm {{ $classes }}" x-data="{ show: true }" x-show="show">
            <div class="flex items-start justify-between gap-4">
                <span>{{ session($key) }}</span>
                <button type="button" @click="show = false" aria-label="Dismiss">&times;</button>
            </div>
        </div>
    @endif
@endforeach
