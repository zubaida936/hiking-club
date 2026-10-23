<x-site.auth-card title="Choose a new password">
    <p class="mb-6 rounded-xl bg-brand-50 px-5 py-4 text-sm text-brand-900" role="status">
        If {{ $phone }} has an account with an email address, we've sent a 6-digit code to that email.
        It works for {{ config('members.code_ttl_minutes') }} minutes.
    </p>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-5" novalidate>
        @csrf
        <x-site.field name="code" label="Code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required placeholder="123456" />
        <x-site.password-field label="New password" autocomplete="new-password" :hint="'At least '.config('members.password_min_length').' characters.'" />
        <x-site.password-field name="password_confirmation" label="Repeat the password" autocomplete="new-password" />
        <button class="w-full rounded-full bg-accent-500 px-8 py-3 font-display font-bold uppercase tracking-wide text-white hover:bg-accent-600">Save new password</button>
    </form>

    @php($whatsapp = \App\Support\WhatsApp::link('Hi! I need help resetting the password of my hiking club account.'))
    <p class="mt-8 text-center text-sm text-slate-600">
        No code? <a href="{{ route('password.request') }}" class="font-semibold text-brand-700 hover:underline">Send a new one</a>
        @if ($whatsapp)
            <br>No email on your account? <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="font-semibold text-brand-700 hover:underline">Message us on WhatsApp</a>
        @endif
    </p>
</x-site.auth-card>
