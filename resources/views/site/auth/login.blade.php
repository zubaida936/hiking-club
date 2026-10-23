<x-site.auth-card title="Log in" intro="See your points, rank and bookings.">
    <form method="POST" action="{{ route('login.store') }}" class="space-y-5" novalidate>
        @csrf
        <x-site.field name="phone" label="Phone number" type="tel" required autocomplete="tel" placeholder="+961 70 123 456" />
        <x-site.password-field />
        <div class="flex items-center justify-between gap-4 text-sm">
            <label class="flex items-center gap-2 text-slate-700">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"> Remember me
            </label>
            <a href="{{ route('password.request') }}" class="font-semibold text-brand-700 hover:underline">Forgot password?</a>
        </div>
        <button class="w-full rounded-full bg-accent-500 px-8 py-3 font-display font-bold uppercase tracking-wide text-white hover:bg-accent-600">Log in</button>
    </form>

    <div class="mt-10 rounded-2xl bg-brand-50 p-6 text-center">
        <p class="font-semibold text-brand-900">Hiked with us but no account yet?</p>
        <p class="mt-1 text-sm text-slate-600">Accounts are for hikers who booked a hike. Activate yours with your booking reference.</p>
        <a href="{{ route('activate') }}" class="mt-4 inline-block font-display font-semibold uppercase tracking-wide text-brand-600 hover:text-brand-800">Activate my account &rarr;</a>
    </div>
</x-site.auth-card>
