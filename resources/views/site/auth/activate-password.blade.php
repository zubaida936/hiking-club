<x-site.auth-card title="Choose a password" :intro="'Hi '.$hiker->name.'! Last step: choose the password for your account.'">
    <form method="POST" action="{{ route('activate.store') }}" class="space-y-5" novalidate>
        @csrf
        <p class="text-sm text-slate-600">You'll log in with <strong>{{ $hiker->phone }}</strong> and this password.</p>
        <x-site.password-field label="New password" autocomplete="new-password" :hint="'At least '.config('members.password_min_length').' characters.'" />
        <x-site.password-field name="password_confirmation" label="Repeat the password" autocomplete="new-password" />
        <button class="w-full rounded-full bg-accent-500 px-8 py-3 font-display font-bold uppercase tracking-wide text-white hover:bg-accent-600">Activate my account</button>
    </form>
</x-site.auth-card>
