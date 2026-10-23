<x-site.auth-card title="Forgot your password?" intro="We'll send you a code to choose a new one.">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5" novalidate>
        @csrf
        <x-site.field name="phone" label="Your phone number" type="tel" required autocomplete="tel" placeholder="+961 70 123 456" />
        <button class="w-full rounded-full bg-accent-500 px-8 py-3 font-display font-bold uppercase tracking-wide text-white hover:bg-accent-600">Send me a code</button>
    </form>
    <p class="mt-8 text-center text-sm"><a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">&larr; Back to log in</a></p>
</x-site.auth-card>
