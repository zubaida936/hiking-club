<x-site.auth-card title="Activate your account" intro="For hikers who booked a hike with us: confirm it's you, then choose a password.">
    <form method="POST" action="{{ route('activate.verify') }}" class="space-y-5" novalidate>
        @csrf
        <x-site.field name="phone" label="Phone number you booked with" type="tel" required autocomplete="tel" placeholder="+961 70 123 456" />
        <x-site.field name="reference" label="Booking reference" required autocomplete="off" placeholder="e.g. K7F3Q2MZ" class="uppercase"
                      hint="The 8-character code on your booking confirmation page or email, from any hike you booked." />
        <button class="w-full rounded-full bg-accent-500 px-8 py-3 font-display font-bold uppercase tracking-wide text-white hover:bg-accent-600">Continue</button>
    </form>

    @php($whatsapp = \App\Support\WhatsApp::link('Hi! I need help activating my hiking club account.'))
    <p class="mt-8 text-center text-sm text-slate-600">
        Already activated? <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Log in</a>
        @if ($whatsapp)
            <br>Lost your booking reference? <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="font-semibold text-brand-700 hover:underline">Message us on WhatsApp</a>
        @endif
    </p>
</x-site.auth-card>
