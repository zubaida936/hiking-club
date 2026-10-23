{{-- The invite / password link just created for a member, ready to copy or send on WhatsApp. --}}
@if ($link = session('memberLink'))
    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4" x-data="{ copied: false }">
        <p class="text-sm font-semibold text-emerald-900">Link for {{ $link['hiker'] }}: send it to them (it only works for them and expires).</p>
        <div class="mt-2 flex flex-wrap gap-2">
            <input x-ref="link" readonly value="{{ $link['url'] }}" class="min-w-0 flex-1 rounded-md border border-emerald-200 bg-white px-3 py-2 font-mono text-xs">
            <x-admin.button variant="secondary" type="button" @click="navigator.clipboard.writeText($refs.link.value); copied = true">
                <span x-text="copied ? 'Copied!' : 'Copy'">Copy</span>
            </x-admin.button>
            <x-admin.button :href="$link['whatsapp']" target="_blank" rel="noopener">Send on WhatsApp</x-admin.button>
        </div>
    </div>
@endif
