<x-site.message-page title="Test payment">
    <p class="rounded-xl bg-amber-50 px-5 py-4 text-sm text-amber-900">
        This is the <strong>test gateway</strong>: no real money moves. A real provider (Whish Money, OMT Pay, a bank) replaces this page.
    </p>
    <p class="mt-6">
        {{ $payment->registration->event->title }} · booking {{ $payment->registration->reference }}<br>
        <strong class="text-2xl text-brand-900">{{ $payment->formattedAmount() }} {{ $payment->currency }}</strong>
    </p>
    <form method="POST" action="{{ route('payments.test.simulate', $payment->external_id) }}" class="mt-8 flex justify-center gap-3">
        @csrf
        <button name="outcome" value="pay" class="rounded-full bg-accent-500 px-8 py-3 font-display font-bold uppercase tracking-wide text-white hover:bg-accent-600">Pay</button>
        <button name="outcome" value="fail" class="rounded-full border border-slate-300 px-8 py-3 font-display font-bold uppercase tracking-wide text-slate-700 hover:bg-slate-50">Fail</button>
    </form>
</x-site.message-page>
