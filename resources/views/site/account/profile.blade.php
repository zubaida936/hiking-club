<x-site.auth-card title="My profile" intro="Your details are filled in when you book a hike.">
    <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-5" novalidate>
        @csrf
        <x-site.field name="name" label="Full name" :value="$user->name" required maxlength="120" autocomplete="name" />
        <div>
            <p class="block text-sm font-semibold text-slate-700">Phone number</p>
            <p class="mt-1 text-slate-800">{{ $user->phone }}</p>
            <p class="mt-1 text-xs text-slate-500">Your phone number is your login. To change it, message us on WhatsApp.</p>
        </div>
        <x-site.field name="email" label="Email" type="email" :value="$user->email" :optional="true" autocomplete="email"
                      hint="Your password-reset codes are sent here." />
        <x-site.password-field name="current_password" label="Current password (only to change your email)" />

        <fieldset class="space-y-4">
            <legend class="font-display text-lg font-bold uppercase tracking-wide text-brand-900">Emergency contact</legend>
            <x-site.field name="emergency_contact_name" label="Name" :value="$hiker?->emergency_contact_name" :optional="true" maxlength="120" />
            <x-site.field name="emergency_contact_phone" label="Phone" type="tel" :value="$hiker?->emergency_contact_phone" :optional="true" placeholder="+961 3 123 456" />
        </fieldset>

        <button class="w-full rounded-full bg-accent-500 px-8 py-3 font-display font-bold uppercase tracking-wide text-white hover:bg-accent-600">Save</button>
    </form>

    <p class="mt-6 text-center">
        <a href="{{ route('account') }}" class="font-semibold text-brand-700 hover:underline">Back to my account</a>
    </p>
</x-site.auth-card>
