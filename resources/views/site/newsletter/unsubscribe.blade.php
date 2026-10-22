<x-site.message-page title="Unsubscribe">
    @if ($subscriber->isActive())
        <p>Stop receiving Next Destination Alerts at <strong class="text-slate-800">{{ $subscriber->email ?? $subscriber->phone }}</strong>?</p>
        <form method="POST" action="{{ route('newsletter.unsubscribe.confirm', $subscriber->token) }}" class="mt-8">
            @csrf
            <button class="rounded-full bg-brand-600 px-6 py-3 font-display font-bold uppercase tracking-wide text-white hover:bg-brand-700">
                Unsubscribe
            </button>
        </form>
    @else
        <p>You're already unsubscribed. You won't get any more alerts from us.</p>
    @endif
</x-site.message-page>
