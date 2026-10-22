<div class="bg-accent-500 text-sm text-white" role="region" aria-label="Next event">
    <div class="mx-auto flex max-w-7xl items-center justify-center gap-3 px-4 py-2 sm:px-6">
        <p class="min-w-0 truncate">
            <span class="font-display font-bold uppercase tracking-wide">Next hike:</span>
            <span class="font-medium">{{ $event->title }}</span>
            <span aria-hidden="true">&middot;</span>
            <span>{{ $when() }}</span>
        </p>
        <a href="{{ $url() }}" class="shrink-0 rounded-full bg-white/20 px-3 py-1 font-display text-xs font-bold uppercase tracking-wide hover:bg-white hover:text-accent-600">
            View Event
        </a>
    </div>
</div>
