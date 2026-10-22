@props(['message' => null])

@php($url = \App\Support\WhatsApp::link($message ?? config('site.contact.whatsapp_message')))

@if ($url)
    <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp"
       class="group fixed bottom-5 right-5 z-50 flex items-center gap-2 rounded-full bg-[#25D366] p-3.5 text-white shadow-lg shadow-black/20 transition hover:bg-[#1ebe5b] focus:outline-none focus-visible:ring-4 focus-visible:ring-[#25D366]/40 sm:bottom-6 sm:right-6">
        <x-site.social-icon name="whatsapp" class="size-7" />
        <span class="hidden pr-1 font-display text-sm font-semibold uppercase tracking-wide sm:group-hover:inline">Chat with us</span>
    </a>
@endif
